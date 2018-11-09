<?php

namespace Drupal\top30\Controller;

use Drupal\custom\Classes\Custom;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Render\FormattableMarkup;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class Top30Controller.
 */
class Top30Controller extends ControllerBase {

  private $table = 'top30_tokens';

  /**
   * Getlist.
   *
   * @return string
   *   Return Hello object.
   */
  public function getList() {

    global $base_url;

    $conn = \Drupal::database();
    $query = $conn->select($this->table, 't')
    ->fields('t')
    ->orderBy('id', 'DESC')
    ->execute();

    $lists = $query->fetchAll(\PDO::FETCH_OBJ);

    $header = array(
      $this->t('Action'),
      $this->t('TOP30'),
      $this->t('Total')
    );

    $db = \Drupal::database();
    $query = $db->select('top30_tokens', 'ts');
    $query->fields('ts', array('token', 'date_debut', 'date_fin'));

    $table_sort = $query->extend('Drupal\Core\Database\Query\TableSortExtender')
    ->orderByHeader($header);

    $pager = $table_sort->extend('Drupal\Core\Database\Query\PagerSelectExtender')
    ->limit(2);

    $result = $pager->execute();

    $rows = array();
    foreach($result as $row) {
      $rows[] = array(
        new FormattableMarkup('<a href=":link">@name</a>', [':link' => $base_url . '/admin/top30/edit/' . $row->token, '@name' => Custom::fixDateTM($row->date_debut, "%d %B %y").' '. $this->t('au') .' '.Custom::fixDateTM($row->date_fin, "%d %B %y")]),
        0
      );
    }

    // Generate the table.
    $build['config_table'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    );

    // Finally add the pager.
    $build['pager'] = array(
      '#type' => 'pager'
    );

    return [
      '#theme'     => 'lists_top30',
      '#listtop30' => $build,
      '#attached'  => array(
        'library'  => array(
          'top30/top30.assets'
        )
      )
    ];

  }

  /**
   * add.
   *
   * @return form
   *   Return Form.
   */
  public function add(Request $request) {

    $isAjax = \Drupal::request()->isXmlHttpRequest();
    $setting_top30 = array();
    $errors = array();

    if ($isAjax) {
      if ( \Drupal::request()->getMethod() == 'POST' ){
        return $this->storeRequest($request, 'add');
      } 
    } else {

      $preview_classement = array();

      $db = \Drupal::database();
      $query = $db->select($this->table, 'ts');
      $query->fields('ts', array('token', 'date_debut', 'date_fin'));

      foreach ($query->execute() as $row) {
        $preview_classement []= array(
          'date'  => Custom::fixDateTM($row->date_debut, "%d %B %y").' '. $this->t('au') .' '.Custom::fixDateTM($row->date_fin, "%d %B %y"),
          'token' => $row->token
        );
      }

      return [
        '#theme' => 'add_top30',
        '#clips' => $this->getListClips(),
        '#preview_classement' => $preview_classement,
        '#attached' => array(
          'library' => array(
            'top30/top30.assets'
          )
        )
      ];
    }

  }

  /**
   * add.
   *
   * @return form
   *   Return Form.
   */
  public function edit(Request $request) {

    $isAjax = \Drupal::request()->isXmlHttpRequest();
    $config_factory = \Drupal::configFactory();
    $setting_top30 = array();
    $errors = array();

    if ($isAjax) {
      if ( \Drupal::request()->getMethod() == 'POST' ){
        return $this->storeRequest($request, 'edit');
      } 
    } else {
      $token = $request->get('token');
      $top30 = array();
      $config_setting = $config_factory->getEditable('top30.settings.' . $token);

      $top30['date_debut_vote'] = $config_setting->get('date_debut_vote');
      $top30['date_fin_vote'] = $config_setting->get('date_fin_vote');
      $top30['preview_classement'] = $config_setting->get('preview_classement');
      $top30['date_pub_classement'] = $config_setting->get('date_pub_classement');
      $top30['share_classement'] = $config_setting->get('share_classement');
      $top30['token'] = $token;

      foreach ($config_setting->get('data_music') as $music) {
        $top30['music'][] = json_decode($music, TRUE);
      }

      $preview_classement = array();

      $db = \Drupal::database();
      $query = $db->select($this->table, 'ts');
      $query->fields('ts', array('token', 'date_debut', 'date_fin'));

      foreach ($query->execute() as $row) {
        $preview_classement []= array(
          'date'  => Custom::fixDateTM($row->date_debut, "%d %B %y").' '. $this->t('au') .' '.Custom::fixDateTM($row->date_fin, "%d %B %y"),
          'token' => $row->token
        );
      }

      /*------------------------- Sort Music By total Rates -------------------------*/
      $top30['music'] = custom::array_orderby($top30['music'], 'position', SORT_ASC);

      $cpt = 1;
      $preview_token = $config_setting->get('preview_classement');
      $preview_classement_music = $config_factory->getEditable('top30.settings.' . $preview_token );

      foreach ($top30['music'] as &$music) {
        $clip = array_values($this->getListClips($music['track']));
        $preview_music = $preview_classement_music->get('data_music.' . $music['track']);
        $music['title'] = $clip[0]->get('title')->getValue()[0]['value'];
        $music['position'] = $cpt;
        $cpt++;

        if (!empty($preview_music)) {
          $preview_music = json_decode($preview_music, TRUE);
          if ($preview_music['total_rates'] < $music['total_rates']) {
            $music['pregression'] = '<span class="prog-up">&nbsp;+' . intval($music['total_rates'] - $preview_music['total_rates']) . '</span>';
          } elseif ($preview_music['total_rates'] > $music['total_rates']) {
            $music['pregression'] = '<span class="prog-down">&nbsp;-' . intval($preview_music['total_rates'] - $music['total_rates']) . '</span>';
          } else {
            $music['pregression'] = '<span class="prog-bar"></span>';
          }
          $music['preview_position'] = $preview_music['position'];
        } else {
          $music['preview_position'] = '';
        }
      }

      return [
        '#theme' => 'edit_top30',
        '#clips' => $this->getListClips(),
        '#top30' => $top30,
        '#preview_classement' => $preview_classement,
        '#attached' => array(
          'library' => array(
            'top30/top30.assets'
          )
        )
      ];
    }
  }

  /**
   * Getlist.
   *
   * @return entity object
   *   Return Entity Object.
   */
  public function getListClips($nid = NULL) {

    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'clips');
    if ($nid !== NULL) {
      $query->condition('nid', $nid);
    }
    $query->condition('status', 1);
    $nids = $query->execute();

    $clips = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($nids);

    return $clips;

  }

  /**
   * Getlist.
   *
   * @return json
   *   Return Json.
   */
  public function voteClip(Request $request) {

    $nid = $request->get('nid');
    $token = $request->get('token');
    $user_id = Custom::checkUserConnected();
    /*----------------- Check exist vote user ----------------*/
    $conn = \Drupal\Core\Database\Database::getConnection();
    $query = $conn->select('vote_users', 'vu');
    $query->addExpression('COUNT(*)');
    $query->condition('user_id', $user_id);
    $query->condition('token', $token);
    $global_count = $query->execute()->fetchField();
    $query->condition('nid', $nid);
    $count_nid = $query->execute()->fetchField();

    if ($global_count >= 5) {
      return new JsonResponse(array(
        'danger' => array(
          'message' => $this->t('Vous avez déjà voté à 5 clips dans cette semaine')
        )
      ));
    } elseif ($count_nid > 0) {
      return new JsonResponse(array(
        'danger' => array(
          'message' => $this->t('Vous avez déjà voté à ce clip')
        )
      ));
    } elseif ($global_count < 5) {

      $data = array(
        'user_id' => $user_id,
        'nid'     => $nid,
        'token'   => $token
      );

      $insert = $conn->insert('vote_users')->fields($data)->execute();

      $config_factory = \Drupal::configFactory();
      $config_data = $config_factory->getEditable('top30.settings.' . $token);
      $clip = json_decode($config_data->get('data_music.' . $nid), TRUE);


      $clip['total_rates'] = $clip['total_rates'] + 1;
      $config_data->set('data_music.' . $nid, json_encode($clip))->save();


      return new JsonResponse(array(
        'success' => array(
          'message' => $this->t('Merci pout votre vote')
        )
      ));
    }

  }

  /**
   * GetTOP30.
   *
   * @return object
   *   Return Object.
   */
  public function getTOP30($view = NULL, $range = NULL) {

   $isAjax = \Drupal::request()->isXmlHttpRequest();
   $clips = array();
   $top30 = $this->publichThisClassement()[0];
   $token = $top30->token;
   $currentDate = date('Y-m-d H:i:s');

   if ( !empty($top30) AND $currentDate >= $top30->maxdate ) {

    $config_factory = \Drupal::configFactory();
    $config_data = $config_factory->getEditable('top30.settings.' . $token);
    $preview_token = $config_data->get('preview_classement');

    foreach ($config_data->get('data_music') as $music) {
      $music = json_decode($music, TRUE);

      $nid = \Drupal::entityQuery('node')
      ->condition('type', 'clips')
      ->condition('nid', $music['track'])
      ->condition('status', 1)
      ->execute();

      $clip = array_values(\Drupal::entityTypeManager()->getStorage('node')->loadMultiple($nid))[0];

      $user_id = Custom::checkUserConnected();
      /*----------------- Check exist vote user ----------------*/
      $conn = \Drupal\Core\Database\Database::getConnection();
      $query = $conn->select('vote_users', 'vu');
      $query->addExpression('COUNT(*)');
      $query->condition('user_id', $user_id);
      $query->condition('token', $token);
      $query->condition('nid', $music['track']);
      $count = $query->execute()->fetchField();

      $preview_top30 = $config_factory->getEditable('top30.settings.' . $preview_token );
      $preview_music = json_decode($preview_top30->get('data_music.' . $music['track']), TRUE);

      if (!empty($preview_music)) {
        if ($preview_music['total_rates'] < $music['total_rates']) {
          $music['pregression'] = '<i class="fa fa-chevron-up" aria-hidden="true"></i>';
        } elseif ($preview_music['total_rates'] > $music['total_rates']) {
          $music['pregression'] = '<i class="fa fa-chevron-down" aria-hidden="true"></i>';
        } else {
          $music['pregression'] = '<i class="fa fa-bars" aria-hidden="true"></i>';
        }
      }

      $clips []= array(
        'nid'     => $music['track'],
        'title'   => $clip->get('title')->getValue()[0]['value'],
        'image'   => ( $clip->get('field_image_video_clip')->getValue() != NULL ? Custom::generatePathFile($clip->get('field_image_video_clip')->getValue()[0]['target_id'], TRUE) : NULL ),
        'artiste' => taxonomy_term_load($clip->get('field_artiste_clip')->getValue()[0]['target_id'])->name->value,
        'position' => $music['position'],
        'progression' => $music['pregression'],
        'embed_code' => $clip->get('field_embed_code_clip')->getValue()[0]['value'],
        'exist_vote' => $count
      );
    }

    $formatted_date_pub = $this->t('Du') . ' ' . Custom::fixDateTM(explode('&', $config_data->get('date_pub_classement'))[0], "%d %B %Y");

    } else {
      $clips = $formatted_date_pub = $token = NULL;
    }

    /*------------------------- Sort Music By total Rates -------------------------*/
    $clips = custom::array_orderby($clips, 'position', SORT_ASC);

    if (!empty($range)) {
      $clips = array_slice($clips, 0, $range);
    }

    $build = [
      '#theme' => $view,
      '#clips' => $clips,
      '#token' => $token,
      '#date_classement' => $formatted_date_pub,
      '#attached'  => array(
        'library'  => array(
          'top30/top30_front.assets'
        )
      )
    ];

    /*------------ Check Ajax Request Fetch ------------*/
    if ($isAjax) {
      $type_view = \Drupal::request()->get('type_view');
    
      if ($type_view == 'float') {
        $view = 'fetch_clips_top30';
      } else {
        $view = 'list_clips_top30';
      }

      $build = [
        '#theme'  => $view,
        '#clips'  => $clips,
        '#token'  => $token,
        '#isAjax' => TRUE,
        '#date_classement' => $formatted_date_pub
      ];

      return new JsonResponse([
        'result' => render($build)
      ]);
    }

    return $build;

  }

  public function publichThisClassement() {

    $conn = \Drupal\Core\Database\Database::getConnection();
    $query = $conn->select($this->table, 'ts');
    $query->fields('ts', ['token']);
    $query->addExpression('COUNT(*)');
    $query->addExpression('MAX(date_pub)', 'maxdate');
    $query->condition('is_published', 1);
    $query->groupBy('id');
    $result = $query->execute()->fetchAll();

    return $result;

  }

  /**
   * Getlist.
   *
   * @return entity object
   *   Return Entity Object.
   */
  public function searchClips(Request $request) {

    $data = array();

    $key = $request->get('key');

    $query = \Drupal::entityQuery('node')
    ->condition('type', 'clips')
    ->condition('status', 1);
    $and_condition_1 = $query->orConditionGroup()
    ->condition('title', '%' . $key . '%', 'like')
    ->condition('field_artiste_clip.entity.name', '%' . $key . '%', 'LIKE');
    $nids = $query
    ->condition($and_condition_1)
    ->sort('created', 'DESC')
    ->execute();

    $clips = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($nids);

    foreach ($clips as $clip) {
      if ($clip->hasField('field_image_video_clip')) {
        $field_avatar_sortie = $clip->get('field_image_video_clip')->getValue()[0]['value'];
      } else {
        $field_avatar_sortie = NULL;
      }
      $data []= array(
        'nid'   => $clip->get('nid')->getValue()[0]['value'],
        'title' => $clip->get('title')->getValue()[0]['value'],
        'embed_code'  => $clip->get('field_embed_code_clip')->getValue()[0]['value'],
        'embed_image' => $field_avatar_sortie,
        'artiste' => taxonomy_term_load($clip->get('field_artiste_clip')->getValue()[0]['target_id'])->name->value
      );
    }

    $build = [
      '#theme'  => 'search_clips_top30',
      '#clips'  => $data
    ];

    return new JsonResponse([
     'result' => render($build),
     'count'  => count($nids)
   ]);

  }

  public function validateEmptyFields($fields = array()) {
    $empty = FALSE;

    foreach ($fields as $field) {
      if (empty($field)) {
        $empty = TRUE;
      }
    }

    return $empty;
  }

  public function storeRequest($request, $type) {

    /*-------------------------- Initialize Variables -----------------------*/
    $date_debut_vote = $request->get('date__debut_vote');
    $time_debut_vote = $request->get('time__debut_vote');
    $date_fin_vote = $request->get('date__fin_vote');
    $time_fin_vote = $request->get('time__fin_vote');
    $date_classement_precedent = $request->get('preview-classement');
    $date_publication_classement = $request->get('date__publication_classement');
    $time_publication_classement = $request->get('time__publication_classement');
    $share_classement = $request->get('share_classement');
    $is_published = ( intval($share_classement) == 1 ? $share_classement : 0 );

    $formatted_date_start = Custom::fixDateTM($request->get('date__debut_vote'), "%d %B %y");
    $formatted_date_end = Custom::fixDateTM($request->get('date__fin_vote'), "%d %B %y");
    $date_id = str_replace(' ', '_', $formatted_date_start .'-au-'. $formatted_date_end);
    $date_id = sha1($date_id);

    $data = array(
      'token'        => $date_id,
      'date_debut'   => $date_debut_vote . ' ' . $time_debut_vote,
      'date_fin'     => $date_fin_vote . ' ' . $time_fin_vote,
      'is_published' => $is_published
    );
    /*--------------------------- Validate Form Field -----------------------*/
    $fields = array(
      $date_debut_vote,
      $time_debut_vote,
      $date_fin_vote,
      $time_fin_vote,
      $date_publication_classement,
      $time_publication_classement
    );

    if ($this->validateEmptyFields($fields)) {
      $errors []= array( 
        'message' => $this->t('Merci de completer tous les champs obligatoires') 
      );
    } else {
      /*--------------------------- Check Token Exist -------------------------*/
      $count = 0;
      $conn = \Drupal\Core\Database\Database::getConnection();
      if ($type != 'edit') {
        $query = $conn->select($this->table, 't');
        $query->addExpression('COUNT(*)');
        $query->condition('date_debut', $date_debut_vote . ' ' . $time_debut_vote);
        $query->condition('date_fin', $date_fin_vote . ' ' . $time_fin_vote);
        $count = $query->execute()->fetchField();
      }
      /*-------------------- Insert Data settings mappings --------------------*/
      if ($count == 0) {
        if ($type != 'edit') {
          $insert = $conn->insert($this->table)->fields($data)->execute();
        } else {
          $update = $conn->update($this->table)
          ->fields([
            'date_pub'     => $date_publication_classement .'&'. $time_publication_classement,
            'is_published' => $is_published
          ])
          ->condition('token', $date_id)
          ->execute();
          $insert = 1;
        }

        if ($insert) {

          $config_factory = \Drupal::configFactory();
          $config_factory->getEditable('top30.settings.' . $date_id)
          ->set('date_debut_vote', $date_debut_vote .'&'. $time_debut_vote)
          ->set('date_fin_vote', $date_fin_vote .'&'. $time_fin_vote)
          ->set('preview_classement', $date_classement_precedent)
          ->set('date_pub_classement', $date_publication_classement .'&'. $time_publication_classement)
          ->set('share_classement', $is_published)
          ->save();

          foreach ($request->get('track') as $key => $value) {
            if ($value == 0) {
              continue;
            }
            $setting_top_30_music = array(
              'track'       => $value,
              'position'    => $request->get('position')[$key],
              'total_rates' => $request->get('total_rate')[$key]
            );

            $config_factory->getEditable('top30.settings.' . $date_id)
            ->set('data_music.'  . $value, json_encode($setting_top_30_music))
            ->save();
          }
        }

        if ($type == 'add') {
          $message = $this->t('Top30 à été bien ajouté à la liste.');
        } elseif ($type == 'edit') {
          $token = $request->get('top30_token');
          $delete_music = $request->get('top30_music_delete');

          if (!empty($delete_music)) {
            foreach ($delete_music as $value) {
              $config_setting = $config_factory->getEditable('top30.settings.' . $token);
              $config_setting->clear('data_music.' . $value)->save();
            }
          }
          $message = $this->t('La mise à jour de Top30 à été bien effectué.');
        }

        return new JsonResponse([
          'success' => [ 'message' => $message ] 
        ]);
      } else {
        $errors []= array( 
          'message' => $this->t('Top30 existe déjà veuillez choisir une autre date') 
        );
      }
    }
    return new JsonResponse(
      [ 'errors' => $errors ]
    );
  }

}