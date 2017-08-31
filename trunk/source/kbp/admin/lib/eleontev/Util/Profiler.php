<?php

function dbg_profile($dbg_prof_results = false)
{
        if (!$dbg_prof_results) dbg_get_profiler_results(&$dbg_prof_results);

        $ctx_cache = array();
        $contexts = array();
        foreach ($dbg_prof_results["line_no"] as $idx => $line_no) {
                $mod_no = $dbg_prof_results["mod_no"][$idx];
                dbg_get_module_name($mod_no, &$mod_name);
               
                $hit_cnt = $dbg_prof_results["hit_count"][$idx];

                $time_sum = $dbg_prof_results["tm_sum"][$idx] * 1000;
                $time_avg_hit = $time_sum / $hit_cnt;
                $time_min = $dbg_prof_results["tm_min"][$idx] * 1000;
                $time_max = $dbg_prof_results["tm_max"][$idx] * 1000;

                dbg_get_source_context($mod_no, $line_no, &$ctx_id);
                if (@$ctx_cache[$ctx_id]) {
                        $ctx_name = $ctx_cache[$ctx_id];
                } else {
                        if (dbg_get_context_name($ctx_id, &$ctx_name) && strcmp($ctx_name,"") == 0)
                                $ctx_name = "::main";
                        $ctx_cache[$ctx_id] = $ctx_name;
                }

                $cont =& $contexts[$ctx_name];
                if (!@$cont) $cont = array(
                        "file" => $mod_name,
                        'hit_cnt' => 0,
                        'time_sum' => 0,
                        'lines' => array()
                );

                $cont['hit_cnt'] += $hit_cnt;
                $cont['time_sum'] += $time_sum;
                $cont['lines'][$line_no] = array(
                        "hit_cnt"  => $hit_cnt,
                        "time_sum" => $time_sum,
                );
        }

        uasort($contexts, 'dbg_profile_cmp');
        foreach ($contexts as $ctx=>$data) {
                uasort($contexts[$ctx]['lines'], 'dbg_profile_cmp');
        }
        return $contexts;
}

function dbg_profile_cmp($x,$y)
{
    $a = $x["time_sum"];
    $b = $y["time_sum"];
    if ($a == $b) return 0;
    return ($a > $b) ? -1 : 1;
}


function dbg_draw_contexts($contexts, $lines_detail = 3)
{
        echo "
            <br><table cellspacing=2 cellpadding=2 border=0 style='font:8pt courier'>
            <thead>
                <tr style='background:#808080; color:#FFFFFF'>
                        <td> file </td>
                        <td> function </td>
                        <td> hit_cnt </td>
                        <td> time </td>
                        <td> % time </td>
                </tr></thead>
                <tbody style='vertical-align: top'>
        ";

        $total_time = $total_hits = 0;
        foreach ($contexts as $context=>$data) {
                $total_time += $data['time_sum'];
                $total_hits += $data['hit_cnt'];
        }

        $idx = 0;
        foreach ($contexts as $context=>$data) {
                $bk = ($idx++ & 1) ? "#ffffff" : "#e0e0e0";
                $file = basename($data['file']);
                $p_time = sprintf("%.3f", 100 * $data['time_sum'] / $total_time);
                echo @"
                        <tr style='background:$bk'>
                        <td>$file</td>
                        <td>$context</td>
                        <td>$data[hit_cnt]</td>
                        <td>$data[time_sum]</td>
                        <td>$p_time</td>
                    </tr>
                ";
                if ($idx-1 < $lines_detail) {
                        $i = 0;
                        foreach ($data['lines'] as $l=>$d) {
                                $p_time = sprintf("%.3f", 100 * $d['time_sum'] / $total_time);
                                echo @"
                                        <tr style='background:$bk'>
                                        <td>...</td>
                                        <td>&&line $l</td>
                                        <td>&&$d[hit_cnt]</td>
                                        <td>&&$d[time_sum]</td>
                                        <td>&&$p_time</td>
                                        </tr>
                                ";
                                if ($i++>20) break;
                        }
                }
        }
        echo "</tbody></table>";
}



//Èñïîëüçîâàíèå:
//Êîä (any language):     ñêîïèðîâàòü êîä â áóôåð îáìåíà

/*
//include_once "Profiler.php";

function A() {
        for($i=0; $i<1000; $i++) ;
}

function B() {
        for($i=0; $i<500; $i++) ;
}

A();
B();

dbg_draw_contexts(dbg_profile());
*/



ini_set('allow_call_time_pass_reference', 'On');


function profiler_getResults( /*float*/ $visibleContextPercent = null )
        {
        $DEFAULT_PERSENT = 1.0;
       
                if( is_null($visibleContextPercent) || !is_float($visibleContextPercent) || $visibleContextPercent < 0 )
                $visibleContextPercent = $DEFAULT_PERSENT;
                if( 100 < $visibleContextPercent) $visibleContextPercent = 100;
       
        dbg_get_profiler_results(& $data);
       
        @set_time_limit(0.25*sizeof($data['mod_no']));
       
        $results = array(
                'fileIds'    => $data['mod_no'],
                'contextIds' => array(),
                'lines'      => $data['line_no'],
                'times'      => $data['tm_sum'],
                'hits'       => $data['hit_count'],
                'totalTime'  => array_sum($data['tm_sum'])
                );
       
        $time = $results['totalTime']*$visibleContextPercent/100;
       
                foreach( $results['fileIds'] as $i => $fileId )
                {
                        if( $time <= $results['times'][$i] )
                        {
                        dbg_get_source_context($fileId, $results['lines'][$i], & $contextId);
                        $results['contextIds'][$i] = $contextId;
                        }
                        else $results['contextIds'][$i] = null;
                }
       
        return $results;
        }


function profiler_getFileNames( /*array collection*/ $results, /*string*/ $root = null )
        {
        $names = array();
        $ids = array_values(array_unique($results['fileIds']));
       
        $count = sizeof($ids);
        $length = !is_null($root) ? strlen($root) : null;
                for( $i = 0 ; $i < $count ; $i++ )
                {
                dbg_get_module_name($ids[$i], & $name);
                $name = str_replace('\\', '/', $name);
                        if( !is_null($root) && strncmp($name, $root, $length) == 0 ) $name = substr($name, $length);
                $names[$ids[$i]] = $name;
                }
       
        return $names;
        }


function profiler_getContextNames( /*array collection*/ $results )
        {
        $names = array();
        $ids = array_values(array_unique($results['contextIds']));
       
        $count = sizeof($ids);
                for( $i = 0 ; $i < $count ; $i++ )
                {
                        if( is_null($ids[$i]) ) continue;
                dbg_get_context_name($ids[$i], & $name);
                        if( is_null($name) || $name == '' ) $name = '::main';
                $names[$ids[$i]] = $name;
                }
       
        return $names;
        }


function profiler_getOrder( /*array collection*/ $results )
        {
        $orders = $results['times'];
        arsort($orders, SORT_NUMERIC);
        return array_keys($orders);
        }


function profiler_output( /*int*/ $maxLines = 100, /*float*/ $visibleContextPercent = null )
        {
        $siteRoot = defined('ROOT') ? str_replace('\\', '/', realpath(getcwd().'/'.ROOT)).'/' : null;
        $results  = profiler_getResults($visibleContextPercent);
        $files    = profiler_getFileNames($results, $siteRoot);
        $contexts = profiler_getContextNames($results);
?>
<br>
<table style="background:#ffffff; font:12px courier" cellspacing="1" cellpadding="3" border="0" align="center">
<caption style="background:#ffffff">Total time: <?= number_format(1000*$results['totalTime'], 3) ?> ms</caption>
<col align="right">
<col>
<col>
<col align="right">
<col align="right">
<col align="right">
<col align="right">
<thead>
        <tr style="background:#808080; color:#FFFFFF">
                <td align="center">#</td>
                <td align="center">file</td>
                <td align="center">context</td>
                <td align="center">line</td>
                <td align="center">hits</td>
                <td align="center">time, ms</td>
                <td align="center">time, %</td>
        </tr>
</thead>
<tbody>
<?php

                foreach( profiler_getOrder($results) as $r => $i )
                {
                        if( $maxLines <= $r ) break;
?>
        <tr<?= $r%2 == 0 ? '' : ' style="background:#e0e0e0"' ?>>
                <td><?= $r + 1 ?></td>
                <td><?= $files[$results['fileIds'][$i]] ?></td>
                <td><?= is_null($results['contextIds'][$i]) ? '&mdash;' : $contexts[$results['contextIds'][$i]] ?></td>
                <td><?= $results['lines'][$i] ?></td>
                <td><?= $results['hits'][$i] ?></td>
                <td><?= number_format(1000*$results['times'][$i], 3) ?></td>
                <td><?= number_format(100*$results['times'][$i]/$results['totalTime'], 3) ?></td>
        </tr>
<?php


                }
?>
</tbody>
</table>
<br>
<?php


        }


//È èñïîëüçîâàíèå:
//Êîä (any language):     ñêîïèðîâàòü êîä â áóôåð îáìåíà
//require_once('_profiler.inc');

//profiler_output(100, 1.0);

?>