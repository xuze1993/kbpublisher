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


class TriggerBulkSavingHelper
{
    var $manager = false;
    var $vars = false;

    var $initial_states = array();
    var $final_states = array();


    function __construct($manager, $vars) {

        $ids = $vars->id;

        $params = sprintf('AND e.id IN(%s)', implode(',', $ids));
        $manager->setSqlParams($params);
        $rows = $manager->getRecords();

        $tags = $manager->tag_manager->getTagToEntry(implode(',', $ids));

        $obj = new KBEntry;

        foreach ($rows as $row) {
            $obj->set($row);

            if (!empty($tags[$row['id']])) {
                $obj->setTag(array_keys($tags[$row['id']]));
            }

            $this->initial_states[$row['id']] = $manager->getTrackedFields($obj);
            $this->final_states[$row['id']] = $this->initial_states[$row['id']];
        }

        $this->manager = $manager;
        $this->vars = $vars;
    }


    function save() {
        $action = $this->vars->bulk_action;
        $method = 'setVars' . ucwords($action);
        if (method_exists($this, $method)) {
            call_user_func(array($this, $method));

            if (!empty($this->initial_states)) {
                $this->manager->saveStates($this->initial_states, $this->final_states);
            }

        } else {
            $this->manager->saveStates($this->initial_states, $this->final_states);
        }
    }


    function setVarsStatus() {
        $tracked_field = 'active';
        $tracked_field_new_value = $this->vars->value['status'];

        $this->setFinalStateScalar($tracked_field, $tracked_field_new_value);
    }


    function setVarsType() {
        $tracked_field = 'type';
        $tracked_field_new_value = $this->vars->value['type'];

        $this->setFinalStateScalar($tracked_field, $tracked_field_new_value);
    }


    function setVarsTag() {
        $addition = false;
        $tracked_field = 'tag';

        if ($this->vars->value['tag_action'] == 'remove') {
            $tracked_field_new_value = array();

        } elseif ($this->vars->value['tag_action'] == 'set') {
            $tracked_field_new_value = $this->vars->tag;

        } elseif ($this->vars->value['tag_action'] == 'add') {
            $addition = true;
            $tracked_field_new_value = $this->vars->tag;
        }

        $this->setFinalStateArray($tracked_field, $tracked_field_new_value, $addition);
    }


    function setFinalStateArray($tracked_field, $tracked_field_new_value, $addition) {
        sort($tracked_field_new_value);

        foreach (array_keys($this->initial_states) as $k) {
            $compared_value = $tracked_field_new_value;
            if ($addition) { // we need to summarize

                foreach(array_keys($compared_value) as $k1) {
                    if(in_array($compared_value[$k1], $this->initial_states[$k][$tracked_field])) {
                        unset($compared_value[$k1]);
                    }
                }

                $compared_value = array_merge($compared_value, $this->initial_states[$k][$tracked_field]);
                sort($compared_value);
            }

            sort($this->initial_states[$k][$tracked_field]);

            if ($this->initial_states[$k][$tracked_field] == $compared_value) { // hasn't changed
                unset($this->initial_states[$k]);
                unset($this->final_states[$k]);

            } else {
                $this->final_states[$k][$tracked_field] = $compared_value;
            }
        }
    }


    function setFinalStateScalar($tracked_field, $tracked_field_new_value) {
        foreach (array_keys($this->initial_states) as $k) {
            if ($this->initial_states[$k][$tracked_field] == $tracked_field_new_value) { // hasn't changed
                unset($this->initial_states[$k]);
                unset($this->final_states[$k]);

            } else {
                $this->final_states[$k][$tracked_field] = $tracked_field_new_value;
            }
        }
    }
}
?>