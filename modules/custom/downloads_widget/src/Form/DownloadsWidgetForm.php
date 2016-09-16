<?php

namespace Drupal\downloads_widget\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Class DownloadsWidgetForm.
 *
 * @package Drupal\downloads_widget\Form
 */
class DownloadsWidgetForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'downloads_widget_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['filename'] = [
      '#type' => 'select',
      '#title' => $this->t('Filename'),
      '#description' => $this->t('Select the file that you want to download'),
      '#options' => $this->getDocuments(),
      '#size' => 1,
      '#required' => TRUE,
    ];
    $form['pass_phrase'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Pass phrase'),
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
    );
    $form['submit'] = [
        '#type' => 'submit',
        '#value' => t('Download a File'),
    ];


    return $form;
  }

  /**
    * {@inheritdoc}
    */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $uri = $form_state->getValue('filename');
    $fid = \Drupal::database()->select('file_managed', 'f')
      ->condition('f.uri', $uri)
      ->fields('f', array('fid'))
      ->execute()
      ->fetchField();
    if (!$fid) {
      $form_state->setErrorByName('filename', t('You are trying to steal our files. Please stop!'));
    }
    $pass_phrase = \Drupal::database()->select('file__field_pass_phrase', 'ffpp')
      ->condition('ffpp.entity_id', $fid)
      ->fields('ffpp', array('field_pass_phrase_value'))
      ->execute()
      ->fetchField();

    $user_pass_phrase = $form_state->getValue('pass_phrase');

    // Set an error if the user enters the wrong pass phrase
    if ($pass_phrase && $pass_phrase != $user_pass_phrase) {
      $form_state->setErrorByName('pass_phrase', t('You entered 
      the wrong pass phrase. No document for you!'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $uri = $form_state->getValue('filename');
    $response = new BinaryFileResponse($uri);
    $response->setContentDisposition('attachment');
    $form_state->setResponse($response);
  }
  /**
   * Helper function that returns documents.
   */
  public function getDocuments() {
    $results = \Drupal::database()->select('file_managed', 'f')
      ->condition('f.type', 'document')
      ->fields('f', array('filename', 'uri'))
      ->execute()
      ->fetchAll();
    $documents = array();
    foreach ($results as $document) {
      $documents[$document->uri] = $document->filename;
    }
    return $documents;
  }
}
