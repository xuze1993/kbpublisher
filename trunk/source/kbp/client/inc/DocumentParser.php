<?php
// +---------------------------------------------------------------------------+
// | This file is part of the KBPublisher package                              |
// | KPublisher - web based knowledgebase publishing tool                      |
// |                                                                           |
// | Author:  Evgeny Leontev <eleontev@gmail.com>                              |
// | Copyright (c) 2005-2008 Evgeny Leontev                                    |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code.                          |
// +---------------------------------------------------------------------------+


class DocumentParser
{
    
    static function isLink($str) {
        return (strpos($str, '[link:') !== false);
    }
    
    static function isLinkArticle($str) {
        return (strpos($str, '[link:article') !== false);
    }
    
    static function isLinkFile($str) {
        return (strpos($str, '[link:file') !== false);
    }
    
    
    static function &parseLink(&$str, $func, $manager, $inline_entries, 
                                    $article_id = false, $controller = false) {
            
        if(strpos($str, '[link:file') !== false) {

            // $search = "#\[link:file\|(\d+)\]#e";
            // $str = preg_replace($search,
            //     "call_user_func_array(\$func,
            //         array('afile', false, $article_id, false, array('AttachID'=>$1), 1))", $str);

            $search = "#\[link:file\|(\d+)\]#";
            $str = preg_replace_callback(
                $search, 
                function ($matches) use($func, $article_id) {
                    return call_user_func_array($func, 
                        array('afile', false, $article_id, false, 
                            array('AttachID' => $matches[1]), 1)
                        );
                },
                $str);    
        }
        
        // all not existing, private stripped
        if(strpos($str, '[link:article') !== false) {
        
            if($controller->mod_rewrite == 3) {
                
                $search = "#\[link:article\|(\d+)\]#";
                preg_match_all($search, $str, $match);
                $match = (isset($match[1])) ? $match[1] : array();
                
                foreach($match as $id) {
                    if(isset($inline_entries[$id])) {
                        $row = $inline_entries[$id];        
                        $entry_id = $controller->getEntryLinkParams($id, $row['title'], $row['url_title']);
                        
                        // $search = "#\[link:article\|(" . $id . ")\]#e";
                        // $str = preg_replace($search, "call_user_func_array(\$func, array('entry', 'false', \$entry_id))", $str);
                    
                        $search = "#\[link:article\|(" . $id . ")\]#";
                        $str = preg_replace_callback(
                            $search, 
                            function ($matches) use($func, $entry_id) {
                                return call_user_func_array($func, 
                                    array('entry', false, $entry_id)
                                    );
                            },
                            $str);
                    }
                }
                
            } else {

                $ids = implode('|', array_keys($inline_entries));
                // $search = "#\[link:article\|(" . $ids . ")\]#e";
                // $str = preg_replace($search, "call_user_func_array(\$func, array('entry', 'false', $1))", $str);
                
                $search = "#\[link:article\|(" . $ids . ")\]#";
                $str = preg_replace_callback(
                    $search, 
                    function ($matches) use($func) {
                        return call_user_func_array($func, 
                            array('entry', false, $matches[1])
                            );
                    },
                    $str);
            }
                
            DocumentParser::parseLinkDoEmpty($str);
            //echo '<pre>', print_r($inline_entries, 1), '</pre>';
        }        
        
        return $str;
    }
    
    
    static function &parseLinkDoEmpty(&$str) {
        if(DocumentParser::isLink($str)) {
            $search = '#<a href="\[link:(file|article)\|\d+\]">(.*?)<\/a>#';
            $str = preg_replace($search, '$2', $str);        
        }
        
        return $str;
    }
    
    
    static function isTemplate($str) {
        return (strpos($str, '[tmpl:') !== false);
    }
    
    
    static function &parseTemplate(&$str, $func) {
        static $i = 1; $i++;
        
        if(strpos($str, '[tmpl:include') !== false) {
            // $search = "#\[tmpl:include\|(\w+)\]#e";
            // $str = preg_replace($search, "call_user_func_array(\$func, array('$1'))", $str);

            $search = "#\[tmpl:include\|(\w+)\]#";
            $str = preg_replace_callback(
                $search, 
                function ($matches) use($func) {
                    return call_user_func_array($func, array($matches[1]));
                },
                $str);
        }
        
        if(DocumentParser::isTemplate($str) && $i <= 5) {
            DocumentParser::parseTemplate($str, $func);
        }
        
        return $str;
    }
    
    
    // replace {} to &#123; &#125; to not strip by template engine
    static function &parseCurlyBraces(&$str) {
        if((strpos($str, '{') !== false)) {
            RequestDataUtil::stripVarsCurlyBraces($str, true);
        }
        
        return $str;
    }


    static function &parseCurlyBracesSimple(&$str) {        
        RequestDataUtil::stripVarsCurlyBraces($str, true, true); // last true for simple (str_replace)
        return $str;
    }


    static function _replace_glossary_item2(&$string, $k, $d, $once, $js_key) {
        
        $string_low = _strtolower($string); // for case insensitive _strpos
        $k_low = _strtolower($k);

        $super_delim = "[ )('\"]|\\s|&nbsp;|&#160;|&#x[aA]0;";
        $left_delim = "(?<! \/\* skip \*\/\")(>|^|{$super_delim})";
        $right_delim = "(<|$|\.|,|:|;|(?:{$super_delim})(?![^<]*>))";

        if (stripos($d, ' href') !== false) {
            $change = '<span class="glossaryItem" onClick="Tip(glosarry_items[%s], FOLLOWMOUSE, false); /* skip */">%s</span>';
        } else {
            $change = '<span class="glossaryItem" onClick="Tip(glosarry_items[%s]);" onMouseOut="UnTip(); /* skip */">%s</span>';
        }
        $change = sprintf($change, $js_key + 1, $k); // replacement for $k

        $ofs = 0; // offset
        $k_len = _strlen($k);
        $intervals = array(); // array of substrings of $string found between $k entries
        while (true) {
            $p = _strpos($string_low, $k_low, $ofs); // find $k entry starting from offset            
            if ($p === false) { // not found
                $intervals[] = _substr($string, $ofs); // the tail of intervals
                break;
            }
            $intervals[] = _substr($string, $ofs, $p - $ofs); // add this interval
            $ofs = $p + $k_len; // continue after the current $k entry
        }

        // Now we have array of substrings between $k entries.
        // We have to check if the interval before each $k has a valid ending delimiter, and if
        // the interval after each $k has a valid starting delimiter -- then we must replace $k
        // with $change. Otherwise we keep $k inplace.

        $string = ''; // resulting string
        $cnt = count($intervals);
        $replaced = 0; // count of replacements done
        for ($i = 0; $i < $cnt - 1; $i += 1) { // NOT TO THE END!
            $inter = $intervals[$i]; // interval "before"
            $inter_next = $intervals[$i + 1]; // interval "after"

            $string .= $inter; // add "before" anyway
            if (    !($inter == '' && $i > 0) // skip several items sticked together (w/o any space)
                && !($inter_next == '' && ($i < $cnt - 2)) // read above
                && !($once && $replaced > 0)
                && preg_match("/$left_delim\$/iu", $inter) // check "before" for delimiter
                && preg_match("/^$right_delim/iu", $inter_next)) { // check "after" for delimiter

                $string .= $change; // add replacement
                $replaced += 1;
            } else {
                $string .= $k; // add unchanged $k value
            }
        }
        
        $string .= $intervals[$cnt - 1]; // add the last interval

        return $replaced;
    }


    static function _replace_glossary_item(&$string, $k, $d, $once, $js_key, $is_mobile) {
        
        $replaced = 0;
        $k2 = str_replace('/', "\\/", preg_quote($k));

        $s_delim = "[ )('\"]|\\s|&nbsp;|&#160;|&#x[aA]0;";    // delimiters between words, common for Start and End positions
        $s_skip = "(?<! \/\* skip \*\/\")";    // skip already replaced items - it's "(?<!...)" negative loop-behind assertion
        $s_notintag = "(?![^<]*>)";    // skip if it is inside tag - "(?!...)" negative loop-ahead assertion
        $s_start_delim = "{$s_skip}(>|^|{$s_delim})";
        $s_end_delim = "([.!?,:;]|$|{$s_delim}){$s_notintag}";    // char '<' is outside, because it will be "inside tag"

        $s_pattern = "/{$s_start_delim}({$k2})(<|{$s_end_delim})/iu";
        
        $str = '$1<span class="glossaryItem _tooltip_custom_glossary" title="" onClick="$(this).tooltipster(\'content\', glosarry_items[%s]).tooltipster(\'show\');" onmouseout="closeTooltip(this);">$2</span>$3';
        
        $str = sprintf($str, $js_key + 1);

        $num_replace = ($once) ? $once : (-1);
        $string = preg_replace($s_pattern, $str, $string, $num_replace);
        
        if (strpos($string, '<span class="glossaryItem') !== false) {
            $replaced = 1;
        }
        
        return $replaced;
    }


    static function &parseGlossaryItems(&$string, $glossary, $manager) {
        
        $i = 0;
        $js_key = 0;
        $js_arr = array();

        // gettting all IDs of glossary items which are used in this string
        $ids = array();
        if ($glossary) {
            uasort($glossary, 'kbpSortByLength');
            
            $num_in_array = 50;
            if(count($glossary) <= $num_in_array) {
                $pattern = "/".str_replace(array('\|', '/'), array('|', "\\/"), preg_quote(implode('|', $glossary)))."/iu";
                preg_match_all($pattern, $string, $match); // PREG_OFFSET_CAPTURE
    
            // 2014-04-29, eleontev added array_chunk to reduce preg pattern
            } else {
                $match_collect = array();
                $gl = array_chunk($glossary, $num_in_array, true);
                foreach(array_keys($gl) as $k) {
                    $pattern = "/".str_replace(array('\|', '/'), array('|', "\\/"), preg_quote(implode('|', $gl[$k])))."/iu";
                    preg_match_all($pattern, $string, $match2); // PREG_OFFSET_CAPTURE
                    if (!empty($match2[0])) {
                        $match_collect = array_merge($match_collect, $match2[0]);
                    }
                }

                $match[0] = $match_collect;
            }
            
            
            if (!empty($match[0])) {
                $match[0] = array_unique($match[0]);
                $pattern = "/".str_replace(array('\|', '/'), array('|', "\\/"), preg_quote(implode('|', $match[0])))."/iu";
                $ids = array_keys(preg_grep($pattern, $glossary));
            }
        }

        if ($ids) {
            $glossary = $manager->getGlossaryDefinitions(implode(',', $ids));
            uksort($glossary, 'kbpSortByLength'); // to sort by strlen, shorter first            

            // replacing all glossary items in string
            foreach (array_keys($glossary) as $k) {
                $definition = addslashes(str_replace(array("\r\n", "\n", "\r"), ' ', $glossary[$k]['d']));
                $is_mobile = ($manager->setting['view_format'] == 'mobile') ? 1 : 0;
                $replaced = DocumentParser::_replace_glossary_item($string /* passed by ref */, $k, $definition, $glossary[$k]['o'], $js_key, $is_mobile);
                if ($replaced > 0) {
                    $js_key += 1;
                    $js_arr[$js_key] = $definition;
                }
            }
            
            // adding needed glossary items into javascript
            if (count($js_arr) > 0) {
                $js_str = '
                <script type="text/javascript">
                    var glosarry_items = new Array;
                    %s
                </script>' . "\n\n";            
                
                $js_str2 = "glosarry_items[%s] = '<span class=\"glosarryItem2\">%s</span>';";
                foreach(array_keys($js_arr) as $k) {
                    $js_terms[] = sprintf($js_str2, $k, $js_arr[$k]);

                }

                $js = sprintf($js_str, implode("\n\t\t\t\t", $js_terms));
                $string = $string . $js;                
            }
        }
        
        return $string;
    }


    static function stripHTML($str) {
        
        $search[] = '#<script[^>]*>.*?<\/script>#si';
        $search[] = '#\[tmpl:.*?\]#i';
        $search[] = '#[-_]{2,}#';
        $search[] = '#\[code=(\w*)\].*?\[\/code\]#si';
        $search[] = '#<style[^>]*>.*?<\/style>#si';
        $str = preg_replace($search, '', $str);
        
        $values = array(
            '<br>', '<BR>',  '< /br>', '<li>', '<p>', '<P>', 
            '&nbsp;', "\n", "\r", "\t", '  '
            );
            
        return strip_tags(str_replace($values, ' ', $str));
    }


    static function getSummary($str, $num_signs = '150') {
    
        if($num_signs === 'all') { return $str; }
        if(!$num_signs) { return; }
        
        $str = DocumentParser::stripHTML($str);

        DocumentParser::parseCurlyBracesSimple($str);
        
        return BaseView::getSubstring($str, $num_signs);
    }
 
 
    static function getSummaryQuick($str, $num_signs = '150') {
        DocumentParser::parseCurlyBracesSimple($str);
        return BaseView::getSubstring($str, $num_signs);
    }
    
    
    static function getTitleSearch($str, $words) {

        $words2 = preg_quote($words);
        preg_match_all('#\w{3,}#iu', $words2, $m);

        if(empty($m[0])) {
            return $str;
        }
        
        if(preg_match('#^"(.*?)"$#iu', $words2, $m2)) { // search with quotes, match complete string
            $keywords = array(preg_quote($m2[1]));
            $search = '#(%s)#iu'; // it will not highlight quotes !
        } else {
            $keywords = array_unique($m[0]);
            $search = '#\b(%s)\b#iu';
        }
            
        $search = sprintf($search, implode('|', $keywords));  
        $replace = '<span class="highlightSearch">$0</span>';

        return preg_replace($search, $replace, $str);
    }
    
    
    static function getSummarySearch($str, $words, $num_signs = '150') {

        if(!$num_signs) { return; }

        DocumentParser::parseCurlyBracesSimple($str);
        $str = DocumentParser::stripHTML($str);

        $words2 = preg_quote(trim($words));
        preg_match_all('#\w{3,}#iu', $words2, $m);

        // nothing to highlight
        if(empty($m[0])) {
            return DocumentParser::getSummaryQuick($str, $num_signs);
        }

        if(preg_match('#^"(.*?)"$#iu', $words2, $m2)) { // search with quotes, match complete string
            $keywords = array(preg_quote($m2[1]));
        } else {
            $keywords = array_unique($m[0]);
        }
        
        // $search = '#(?:\S+\s+){0,5}\b(%s)\b(?:\s+\S+){0,5}#imu';
        $search = '#(?:\S+\s+\W?){0,5}(%s)(?:\W?\s+\S+){0,5}#ium'; // added two \W? to catch - ", etc
        $search = sprintf($search, implode('|', $keywords));    
        preg_match_all($search, $str, $m);
        // echo '<pre>', print_r($m, 1), '</pre>';

        if(empty($m[0])) {
            return DocumentParser::getSummaryQuick($str, $num_signs);
        }

        $num_slice = ceil($num_signs/100);
        $sentences = array_slice($m[0], 0, $num_slice);
        
        if(_strlen($str) > $num_signs) {
            $str = '... ' . implode(' ... ', $sentences) . ' ...';
        } else {
            $str = implode(' ... ', $sentences);
        }
        
        
        $str = BaseView::getSubstring($str, $num_signs, ' ...');
        
        $search = '#\b(%s)\b#iu';
        $search = sprintf($search, implode('|', $keywords));    
        $replace = '<span class="highlightSearch">$0</span>';

        return preg_replace($search, $replace, $str);
    }
    
    
    static function tidyCleanRepair(&$str, $charset) {
        
        if(!function_exists('tidy_parse_string')) {
            return $str;
        }
        
        $options = array(
            'clean' => true, 
            // 'output-xhtml' => true, 
            'output-html' => true,
            'word-2000' => true // Removes all proprietary data when an MS Word document has been saved as HTML
        );
        
        $str = tidy_parse_string($str, $options, $charset);
        tidy_clean_repair($str);
        return $str;
    }
    
    
    static function isCode($str) {
        return (stripos($str, '[code') !== false);
    }
    
    
    static function isCode2($str) {
        return (stripos($str, '<code') !== false);
    }
    
    
    static function parseCode(&$str, $manager, $controller, $files = true) {

        $langs = self::getLangList($str, $manager);
        if (empty($langs)) {
            return $str;
        }
        
        
        // replacing
        $search = '#\[code="?(' . implode('|', $langs) . ')"?\](.*?)\[\/code\]#si';
        if($manager->getSetting('article_block_position') == 'bottom') {
            $replace = '<pre class="brush: $1;">$2</pre>';
        } else {
            $replace = '<div style="margin-right: 200px;"><pre class="brush: $1;">$2</pre></div>';
        }
        $str = preg_replace($search, $replace, $str);
        // $str = str_replace(array("<br />", "<p>", "<\p>"), "", $str);
        
        
        if ($files) {
            $path = sprintf('%sjscript/syntax_highlighter', $controller->client_path);
        
            $js = array();
            $js[] = sprintf('<script src="%s/scripts/shCore.js" type="text/javascript"></script>', $path);
    
            $css = array();
            $css[] = sprintf('<link href="%s/styles/shCore.css" rel="stylesheet" type="text/css" />', $path);
            $css[] = sprintf('<link href="%s/styles/shThemeDefault.css" rel="stylesheet" type="text/css" />', $path);
    
            
            $brush = array();
            $brushes = self::getBrushList();
            $brush_str = '<script type="text/javascript" src="%s/scripts/shBrush%s.js"></script>'; 
            foreach ($langs as $lang) {
                $brush_name = (isset($brushes[$lang])) ? $brushes[$lang] : 'Plain';
                $brush[] = sprintf($brush_str, $path, $brush_name);
            }
            
            $clipboardSwf = sprintf('%s/scripts/clipboard.swf', $path);
            $js_exec = '<script type="text/javascript">
                        $(document).ready(function(){
                            SyntaxHighlighter.config.clipboardSwf = "'.$clipboardSwf.'";
                            SyntaxHighlighter.config.stripBrs = true; 
                            SyntaxHighlighter.all();
                        });
                        </script>%s';
    
            $js[] = sprintf($js_exec, implode("\n", $brush)); 
            $str = implode("\n", $css) . "\n" . implode("\n", $js) . "\n" . $str;
        }
    }
        
    
    static function parseCode2(&$str, $controller) {
        $files = self::parseCode2GetFiles($controller);
        $str .= $files . '<script type="text/javascript">
            $(document).ready(function() {
                hljs.initHighlightingOnLoad();  
            });
        </script>';
    }
    
    
    static function parseCode2GetFiles($controller) {
        $str = '<link rel="stylesheet" href="%stools/ckeditor_custom/plugins/codesnippet/lib/highlight/styles/default.css">
            <script type="text/javascript" src="%stools/ckeditor_custom/plugins/codesnippet/lib/highlight/highlight.pack.js"></script>';
        
        $str = sprintf($str, APP_ADMIN_PATH, APP_ADMIN_PATH);
        return $str;
    }
    
    
    static function getBrushList() {
        $brushes = array(
            'as3' => 'AS3',              'actionscript3' => 'AS3',    'bash' => 'Bash',      
            'shell' => 'Bash',           'cf' => 'ColdFusion',        'coldfusion' => 'ColdFusion', 
            'c-sharp' => 'CSharp',       'csharp' => 'CSharp',        'cpp' => 'Cpp',       
            'c' => 'Cpp',                'css' => 'Css',              'delphi' => 'Delphi', 
            'pas' => 'Delphi',           'pascal' => 'Delphi',        'diff' => 'Diff', 
            'patch' => 'Diff',           'erl' => 'Erlang',           'erlang' => 'Erlang',
            'groovy' => 'Groovy',        'js' => 'JScript',           'jscript' => 'JScript',
            'javascript' => 'JScript',   'java' => 'Java',            'jfx' => 'JavaFX',
            'javafx' => 'JavaFX',        'perl' => 'Perl',            'pl' => 'Perl',
            'php' => 'Php',              'plain' => 'Plain',          'text' => 'Plain',
            'ps' => 'PowerShell',        'powershell' => 'PowerShell','py' => 'Python',
            'python' => 'Python',        'rails' => 'Ruby',           'ror' => 'Ruby',
            'ruby' => 'Ruby',            'scala' => 'Scala',          'sql' => 'Sql',
            'vb' => 'Vb',                'vbnet' => 'Vb',             'xml' => 'Xml',
            'xhtml' => 'Xml',            'xslt' => 'Xml',             'html' => 'Xml'
        );
        
        return $brushes;
    }
    
    
    static function getLangList($str) {
        $brushes = self::getBrushList();
        $brushes_preg = implode('|', array_keys($brushes));
        $code_pattern = '#\[code="?(' . $brushes_preg . ')"?\](.*?)\[\/code\]#si';
        preg_match_all($code_pattern, $str, $matches);
        
        $langs = (!empty($matches[1])) ? $matches[1] : array();
        return $langs;
    }
    
    
    static function &parseCodePrint(&$str) {
        $search = '#\[code=\w*\](.*?)\[\/code\]#si';
        $replace = '<div style="border: 1px solid #999; padding: 10px;"><code>$1</code></div>';
        $str = preg_replace($search, $replace, $str);
        return $str;
    }
  

    static function &parseCodePreview(&$str) {
        $search = '#\[code=\w*\](.*?)\[\/code\]#si';
        $msg = 'This block will be parsed by syntax highlighter in normal view.';
        $replace = '<div style="border: 1px solid #999; padding: 10px;">'.$msg.'<br /><code>$1</code></div>';
        $str = preg_replace($search, $replace, $str);
        return $str;
    }


    function isTableContent($str) {
        
        $ret = array();
        
        // not in use in v5.0
        return $ret;
        
        // $pattern = '/<h([1-6])(.*)>(.*)<\/h[1-6]>/Uis';
        $pattern = '/<h([1-6])([^>]*)>(.*?)<\/h[1-6]>/is';
        preg_match_all($pattern, $str, $matches, PREG_SET_ORDER);
        
        if(count($matches) > 2) {
            $ret = $matches;
        }
        
        return $ret;
    }
    
    
    static function &parseTableContent(&$str, $matches) {
                
        $anchors = array(); // to ensure we haven't added two equal anchors
        $toc = array();
        //$toc[] = '<ol id="articleToc" class="tocContent">';
        
        $i = 0; // counter
        $prev_level = 0; // current heading level (1-6)
        $toc_level = 0; // current toc level
        $prev_toc_level = 0; // toc previous level
        $toc_to_heading_level = array(); // toc level => last heading level

        foreach($matches as $heading) {
                        
            if ($toc_level) {
				$prev_level = $level;
			}
            
            $level = $heading[1];
            
            // anchors
            $ret = preg_match('/id=[\'|"](.*)?[\'|"]/i', stripslashes($heading[2]), $anchor);
            if ($ret && $anchor[1] != '') { // already have an id
                $anchor = stripslashes($anchor[1]);
                $add_id = false;
            } else { // create anchor's id from inner text
                $anchor = preg_replace('/\s+/', '-', preg_replace('/[^a-z\s]/', '', strtolower(strip_tags($heading[3]))));
                $add_id = true;
            }
            
            if (!in_array($anchor, $anchors)) {
                $anchors[] = $anchor;
                
            } else { // rename if already exists
                $orig_anchor = $anchor;
                $i = 2;
                while (in_array($anchor, $anchors)) {
                    $anchor = $orig_anchor . '-' . $i;
                    $i++;
                }
                $anchors[] = $anchor;
            }
            
            if ($add_id) { // modifying article's body
                $hstr = '<h%d id="%s"%s>%s</h%d>';
                $new_heading = sprintf($hstr, $level, $anchor, $heading[2], $heading[3], $level);
                $str = substr_replace($str, $new_heading, strpos($str, $heading[0]), strlen($heading[0]));
            }
            
            // title
            $ret = preg_match('/title=[\'|"](.*)?[\'|"]/i', stripslashes($heading[2]), $title);
            if ($ret && $title[1] != '') { // title exists in tag's attributes
                $title = stripslashes($title[1]);
                
            } else { // get from inner text
                $title = $heading[3];   
            }
            
            $title = trim(strip_tags($title));
            
            if ($prev_level < $level) { // new child
                $toc_level ++;
                $prev_toc_level = $toc_level;
                $toc[] = '<ol class="tocContent">';
                    
            } else if ($prev_level > $level) { // we need to jump up

                // find a closest higher level
                for ($possible_level = $toc_level; $possible_level > 0; $possible_level --) {
                    if ($toc_to_heading_level[$possible_level] == $level) { // same level
                        $toc_level = $possible_level;
                        break;
                        
                    } elseif ($toc_to_heading_level[$possible_level] < $level) { // first level above
                        $toc_level = $possible_level + 1;
                        break;
                    }
                }
                
                if ($possible_level == 0) {
                    $toc_level = 1;
                }
                    
                $toc[] = '</li>';
                
                while ($prev_toc_level > $toc_level) {
                    $toc[] = '</ol></li>';
                    $prev_toc_level --;
                }
                
            } else { // remain in the same level
                $toc[] = '</li>';
            }
            
            $toc_to_heading_level[$toc_level] = $level;
            
            // $toc .= sprintf('<li class="tocLevel%d"><a href="#%s">%s</a>', $level, $anchor, $title);
            $toc[] = sprintf('<li><a href="#%s">%s</a>', $anchor, $title);
            $i++;
        }
        
        if ($prev_toc_level > 0) {
            $toc[] = '</li>'; // close the last heading
            
            while ($prev_toc_level - 1 > 0) { // going up to the first level
                $toc[] = '</ol></li>';
                $prev_toc_level --;
            }
        }
        
        $toc[] = '</ol>'; // close toc
        $toc = '<div id="articleToc">' . implode("\n", $toc) . '</div>';
        
        $str = $toc . $str;
        return $str;
    }

}


function kbpSortByLength($a, $b) {
    return _strlen($a) < _strlen($b);
}

?>