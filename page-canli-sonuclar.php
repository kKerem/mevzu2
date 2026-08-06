<?php get_header(); ?>

<div class="container">
  <div class="single-breadcrumb">
      <?php custom_breadcrumbs(); ?>
  </div>

  <?php echo (get_post_meta(get_the_ID(), 'reklamlari_gizle', true) == 0 ? reklam('govde_ust_reklam') : NULL); ?>

  <?php
  $matches_by_league = [];
  
  // Canlı skor verilerini oku
  $json_file_live = get_template_directory() . '/livedata.json';
  if (file_exists($json_file_live)) {
      $json_data = file_get_contents($json_file_live);
      $data = json_decode($json_data, true);
      
      if (!empty($data['m'])) {
          foreach ($data['m'] as $match) {
              $country = isset($match[36][1]) ? $match[36][1] : '';
              $league = isset($match[36][3]) ? $match[36][3] : '';
              $league_name = trim($country . ' ' . $league) ?: 'Diğer Ligler';
              
              $home_team = trim($match[2] ?? '');
              $away_team = trim($match[4] ?? '');
              
              $matches_by_league[$league_name][] = [
                  'home' => $home_team,
                  'away' => $away_team,
                  'status' => trim($match[6] ?? ''),
                  'score' => trim($match[7] ?? ''),
                  'time' => trim($match[16] ?? '')
              ];
          }
      }
  }
  ?>
  
  <style>
      .livescore-container {
          background: #f8f9fa;
          border-radius: 8px;
          padding: 20px;
          box-shadow: 0 4px 15px rgba(0,0,0,0.05);
          margin-top: 20px;
      }
      .league-group {
          margin-bottom: 25px;
          border-radius: 8px;
          overflow: hidden;
          background: #fff;
          border: 1px solid #eaeaea;
      }
      .league-header {
          background: #2c3e50;
          color: #fff;
          padding: 12px 15px;
          font-weight: 600;
          font-size: 15px;
      }
      .match-row {
          display: flex;
          align-items: center;
          padding: 12px 15px;
          border-bottom: 1px solid #f0f0f0;
          transition: background 0.2s;
      }
      .match-row:last-child {
          border-bottom: none;
      }
      .match-row:hover {
          background: #fdfdfd;
      }
      .match-status {
          width: 70px;
          font-size: 13px;
          color: #e74c3c;
          font-weight: 600;
          text-align: center;
      }
      .match-status.not-started {
          color: #7f8c8d;
          font-weight: normal;
      }
      .match-teams {
          flex: 1;
          display: flex;
          justify-content: space-between;
          align-items: center;
      }
      .team-home {
          flex: 1;
          text-align: right;
          padding-right: 15px;
          font-weight: 500;
          color: #34495e;
      }
      .team-away {
          flex: 1;
          text-align: left;
          padding-left: 15px;
          font-weight: 500;
          color: #34495e;
      }
      .match-score {
          width: 60px;
          text-align: center;
          font-weight: bold;
          font-size: 16px;
          background: #ecf0f1;
          color: #2c3e50;
          padding: 4px 0;
          border-radius: 4px;
          letter-spacing: 1px;
      }
      
      @media (max-width: 768px) {
          .team-home {
              text-align: left;
              padding-right: 0;
              margin-bottom: 5px;
          }
          .team-away {
              text-align: left;
              padding-left: 0;
              margin-top: 5px;
          }
          .match-teams {
              flex-direction: column;
              align-items: stretch;
          }
          .match-score {
              margin: 5px 0;
              width: auto;
              align-self: flex-start;
              padding: 2px 10px;
          }
      }
  </style>

  <div class="livescore-container">
      <?php if (!empty($matches_by_league)): ?>
          <?php foreach ($matches_by_league as $league_name => $matches): ?>
              <div class="league-group">
                  <div class="league-header">
                      ⚽ <?php echo esc_html($league_name); ?>
                  </div>
                  <div class="league-matches">
                      <?php foreach ($matches as $item): 
                          $display_status = $item['status'] ?: $item['time'];
                          $display_score = $item['score'] !== '' ? $item['score'] : 'v';
                          
                          $is_live = $item['status'] !== '';
                          $status_class = $is_live ? '' : 'not-started';
                      ?>
                      <div class="match-row">
                          <div class="match-status <?php echo esc_attr($status_class); ?>">
                              <?php 
                                  echo esc_html($display_status); 
                                  if (is_numeric($item['status'])) {
                                      echo "'";
                                  }
                              ?>
                          </div>
                          <div class="match-teams">
                              <div class="team-home"><?php echo esc_html($item['home']); ?></div>
                              <div class="match-score"><?php echo esc_html($display_score); ?></div>
                              <div class="team-away"><?php echo esc_html($item['away']); ?></div>
                          </div>
                      </div>
                      <?php endforeach; ?>
                  </div>
              </div>
          <?php endforeach; ?>
      <?php else: ?>
          <p style="text-align: center; color: #7f8c8d; padding: 30px;">Şu an için canlı maç verisi bulunmamaktadır.</p>
      <?php endif; ?>
  </div>
</div>

<?php get_footer(); ?>