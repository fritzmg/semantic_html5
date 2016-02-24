<?php

/**
 * Contao Open Source CMS
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @package    semantic_html5
 * @copyright  MEN AT WORK 2016
 * @author     David Maack <david.maack@arcor.de>
 * @license    LGPL-3.0+
 */

namespace SemanticHTML5\Backend;

use Contao\Database\Mysqli\Result;

/**
 * Generall Helper Class for handling start and end tags
 */
class TagUtils
{

    /**
     * The table name where the tags are stored
     */
    private $table = null;

    /**
     * Lookup table for mathcing tags
     */
    private $matchingTags = array(
        'sHtml5End'     => 'sHtml5Start',
        'sHtml5Start'   => 'sHtml5End'
    );

    public function __construct($table) {
        $this->table = $table;
    }

    public function createOrupdateCorresppondingTag(Result $item)
    {
        $cTags = $this->getcorrespondingTag($item);

        if ($cTags == null) {
            //create a new tag
            $data = $item->row();
            $data['type'] = $this->matchingTags[$item->type];
            $newId = $this->createTag($data);

            //update the sh5_pid for end tags
            if ($item->type == 'sHtml5End') {
                $data = array('sh5_pid' => $newId);
                $this->updateTag($item->id, $data);
            }
            
        } else {
            //update the first tag, delete the rest
            $blnDelete = false;

            while ($cTags->next()) {
                if (!$blnDelete) {

                    //set the new data
                    $data = array(
                        'sh5_pid' => $item->id,
                        'sh5_type' => $item->sh5_type
                    );
                    $this->updateTag($cTags->id, $data);

                    $blnDelete = true;
                } else {
                    //delete the tag
                    $this->deleteTag($cTags->id);
                }
            }
        }
    }

    /**
     * Return the matching thml start or end tag
     * @param Result $item the Database-Result from the given item
     * @return NULL|Result Null or the corresponding tag
     */
    public function getcorrespondingTag(Result $item)
    {
        $type = $this->matchingTags[$item->type];
        $result = \Database::getInstance()
                    ->prepare('SELECT * FROM ' . $this->table . ' WHERE sh5_pid = ? AND type = ?')
                    ->execute($item->sh5_pid, $type);
        return ($result->numRows == 0) ? null : $result;
    }

    /**
     * Creates a new html5 tag
     * @param array $data The data for the new Tag
     * @return int the id of the new tag
     */
    private function createTag($data)
    {

        if ($data['type'] == 'sHtml5Start'){
            $data['sh5_pid'] = 0;
            $data['sorting'] = $data['sorting'] - 1;
        }else{
            $data['sh5_pid'] = $data['id'];
            $data['sorting'] = $data['sorting'] + 1;
        }

        $data['tstamp']  = time();
        unset($data['id']);

        // Insert the tag
        $result = \Database::getInstance()
                ->prepare("INSERT INTO " . $this->table . " %s")
                ->set($data)
                ->execute();

        return $result->insertId;
    }

    /**
     * Delete a html5 tag depending on the given id
     * @param type $data
     * @return type
     */
    private function deleteTag($id)
    {
        //ToDo: add the UnDo functionality from contao
        $result = \Database::getInstance()
                    ->prepare('DELETE FROM ' . $this->table . ' WHERE id = ? '
                            . 'AND (type = "sHtml5Start" OR type = "sHtml5End")')
                    ->execute($id);
    }

    /**
     * Updates the element with the given data
     * 
     * @param int $id The id of the element
     * @param Array $data the new Data
     */
    private function updateTag($id, $data)
    {

        //update the database
        \Database::getInstance()
                ->prepare('UPDATE ' . $this->table . ' %s WHERE id = ?')
                ->set($data)
                ->execute($id);
    }

}