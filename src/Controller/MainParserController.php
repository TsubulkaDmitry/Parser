<?php

namespace Drupal\main_parser\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\taxonomy\Entity\Term;

class MainParserController extends ControllerBase {

  /**
   * @return array
   */

  public function Run() {


    //$this->studentParser();
    //$this->specialtyAndGroupParser();
    //$this->groupsAndSubject();
    //$this->pushGroupInTaxonomy();

    $output = [];
    $output['#title'] = 'Разработка Базы Данных';
    $output['#markup'] = 'Testing Date Base';
    return $output;
  }

  /**
   * @param $table /sting
   * @param $param /string or array
   *
   * Функция для извлечения данных из одной таблицы
   *
   * @return mixed
   */
  private function selectDateOneTable($table, $param) {
    $query = \Drupal::database()->select($table, 't');
    $query->fields('t', $param);
    $output = $query->execute()->fetchAll();
    return $output;
  }

  /**
   * @param $table
   * @param $arrayKey
   * @param $arrayParam
   *
   * Вставка данных в таблицу БД
   *
   */
  private function insertDate($table, $arrayKey, $arrayParam) {
    $query = \Drupal::database()->insert($table);
    $query->fields($arrayKey);
    $query->values($arrayParam);
    $query->execute();
  }

  /**
   * @param $filename
   * @param $migrationType
   *
   * Функция проверяет наличие файла для миграции
   *
   * @return array|bool
   */
  private function checkFile($filename, $migrationType) {
    $fullPath = drupal_get_path('module', 'main_parser') . '/assets/' . $filename;
    if (file_exists($fullPath)) {
      $output = file($fullPath);
    }
    else {
      drupal_set_message(t('Missing file for migration ' . $migrationType), 'error');
      $output = FALSE;
    }
    return $output;
  }

  /**
   * Функция миграции данных Студентов из CSV файла во внутренние таблицы
   */
  private function studentParser() {
    $newRows = $this->checkFile('students.csv', 'Students');
    foreach ($newRows as $newRow) {
      $newRow = explode(";", $newRow);
      $oldRows = $this->selectDateOneTable('students', array('surname', 'name', 'middle_name', 'status', 'course', 'groups',));
      $check = FALSE;
      foreach ($oldRows as $oldRow) {
        if ($oldRow->surname != $newRow[0] ||
          $oldRow->name != $newRow[1] ||
          $oldRow->middle_name != $newRow[2] ||
          $oldRow->status != $newRow[3] ||
          $oldRow->course != $newRow[4] ||
          $oldRow->groups != $newRow[5]
        ) {
          $check = TRUE;
        }
        else {
          $check = FALSE;
          break;
        }
      }
      if ($check == TRUE){
        $arrayKey = array('surname', 'name', 'middle_name', 'status', 'course', 'groups');
        unset($newRow[6]);
        $this->insertDate('students', $arrayKey, $newRow);
      }
    }
    drupal_set_message(t('Migration Students is over'), 'status');
  }

  /**
   * Функция миграции данных Специальностей из CSV файла во внутренние таблицы
   */
 private function specialtyAndGroupParser (){
   $newRows = $this->checkFile('specialties.csv', 'Specialties');

   foreach ($newRows as $newRow){
     $newRow = explode(";",$newRow);
     $oldRows = $this->selectDateOneTable('specialty',array('abbreviation', 'full_name', 'additional', 'training_form', 'course', 'groups'));
     $check = FALSE;
     foreach ($oldRows as $oldRow){
       $checkGroup = mb_stripos($newRow[6],$oldRow->groups);
       if ($oldRow->training_form != $newRow[1] ||
          $oldRow->abbreviation != $newRow[2] ||
          $oldRow->full_name != $newRow[3] ||
          $oldRow->additional != $newRow[4] ||
          $oldRow->course != $newRow[5] ||
          $checkGroup === FALSE
       ){
         $check = TRUE;
       }else{
         $check = FALSE;
         break;
       }
     }
     if ($check == TRUE){
       $group = explode(",",$newRow[6]);
       foreach ( $group as $value) {
         $arrayKey = array('abbreviation', 'full_name', 'additional', 'training_form', 'course', 'groups');
         $arrayParam = array($newRow[2], $newRow[3], $newRow[4], $newRow[1], $newRow[5], $value);
         unset($newRow[9]);
         $this->insertDate('specialty', $arrayKey, $arrayParam);
       }
     }
   }
   drupal_set_message(t('Migration Specialty is over'), 'status');
 }

  /**
   * @param $str1
   * @param $str2
   *
   * Функция для МНОГОКРАТНОГО перепарсивания строки с предметама
   *
   * @return array
   */
  private function clearSubjectRows($str1, $str2) {
    $array = array_merge(explode("##", $str1), explode("##", $str2));
    $arrayForPush = [];
    $clearValue = [];
    foreach ($array as $value) {
      if (!mb_stristr($value, '#') === FALSE) {
        $value2 = explode("#", $value);
        foreach ($value2 as $forPush) {
          array_push($clearValue, $forPush);
        }
      }
      else {
        array_push($clearValue, $value);
      }
    }
    foreach ($clearValue as $value) {
      if (!mb_stristr($value, ',') === FALSE) {
        $value = mb_substr($value, mb_strpos($value, ",") + 1);
        array_push($arrayForPush, $value);
      }
    }
    return array_unique($arrayForPush);
  }

  /**
   * Функция миграции данных Групп и Студентов из CSV файла во внутренние таблицы
   */
  private function groupsAndSubject (){
    $newRows = $this->checkFile('specialties.csv', 'Groups and Subject');
    foreach ($newRows as $newRow){
      $newRow = explode(";",$newRow);
      $oldRows = $this->selectDateOneTable('groups',array('course', 'groups', 'subject'));
      $check = FALSE;
      foreach ($oldRows as $oldRow){
        $checkGroup = mb_stripos($newRow[6],$oldRow->groups);
        if ($oldRow->course != $newRow[5] ||
          $checkGroup === FALSE
        ){
          $check = TRUE;
        }else{
          $check = FALSE;
          break;
        }
      }
      if ($check == TRUE){
        $arrayClearSubject = $this->clearSubjectRows($newRow[7],$newRow[8]);
        $group = explode(",",$newRow[6]);
        foreach ( $group as $value){
          $arrayKey = array('course', 'groups', 'subject');
          $subject = implode(",",$arrayClearSubject);
          $arrayParam = array($newRow[5], $value, $subject);
          unset($newRow[9]);
          $this->insertDate('groups', $arrayKey, $arrayParam);
        }
      }
    }
    drupal_set_message(t('Migration Groups and Subject is over'), 'status');
  }

  public function pushGroupInTaxonomy (){
    $query = \Drupal::database()->select('groups', 'g');
    $query->fields('g', array('groups'));
    $newDate = $query->execute()->fetchAll();
    foreach ($newDate as $item){
      if ($this->searchGroupEntries($item->groups) === FALSE ){
        $count = count($this->selectDateOneTable('taxonomy_term_field_data',array('tid')))+10;
       // dump($count);
        $this->insertDate('taxonomy_term_field_data',array('tid', 'vid', 'name','langcode','weight','default_langcode'),array( $count,'group', $item->groups,'en', 0, 1));
      }
    }
  }

  private function searchGroupEntries ($param){
    $query = \Drupal::database()->select('taxonomy_term_field_data', 't');
    $query->addField('t', 'tid');
    $query->condition('t.name', $param);
    $query->condition('t.vid', 'group');
    $output = $query->execute()->fetchField();
    return $output;

  }

  public function TestDB (){
    $query = \Drupal::database()->select('groups', 'g');
    $query->fields('g', array('groups'));
    $select = $query->execute()->fetchAll();
    dump($select);


    $query = \Drupal::database()->select('taxonomy_term_field_data', 't');
    $query->addField('t', 'tid');
    $query->condition('t.name', '121212');
    $query->condition('t.vid', 'group');
    $output = $query->execute()->fetchField();

    dump($output);

  }



}