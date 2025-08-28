<?php
  /**
   * FlowJM - The Lookout
   * Main dashboard experience - Desktop web application
   */

  // Define application root
  define('FLOWJM_ROOT', __DIR__);

  // Load configuration
  require_once FLOWJM_ROOT . '/includes/config.php';
  require_once INCLUDES_PATH . 'database.php';
  require_once INCLUDES_PATH . 'helpers.php';
  require_once INCLUDES_PATH . 'auth.php';

  // Load models
  require_once INCLUDES_PATH . 'models/User.php';
  require_once INCLUDES_PATH . 'models/Journey.php';
  require_once INCLUDES_PATH . 'models/Moment.php';
  require_once INCLUDES_PATH . 'models/Fieldnote.php';

  // Load component system
  require_once COMPONENTS_PATH . 'index.php';

  // Initialize authentication
  Auth::init();
  Auth::require();

  // Get current user
  $currentUser = Auth::user();
  if (!$currentUser) {
      Auth::logout();
      redirect('/login.php');
  }

  // Get dashboard data
  $journey = new Journey();
  $moment = new Moment();

  $journeyStats = $journey->getStats($_SESSION['user_id']);
  $momentStats = $moment->getStats($_SESSION['user_id']);

  // Get Circle Journeys (sorted by relevance: deadlines, overdue, recent activity)
  $circleJourneys = $journey->getCircleJourneys($_SESSION['user_id']);

  // Get all active journeys for Camp drawer
  $activeJourneys = $journey->getByUserId($_SESSION['user_id'], 'active', 1, 50);

  // Get Stack - recent moments across all journeys
  $stackMoments = $moment->getRecentByUserId($_SESSION['user_id'], 1, 30);

  ?>
  <!DOCTYPE html>
  <html lang="en">
  <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <meta name="csrf-token" content="<?php echo Auth::generateCsrfToken(); ?>">
      <title>The Lookout - FlowJM</title>

      <!-- Tailwind CSS -->
      <script src="https://cdn.tailwindcss.com"></script>
      <script>
          tailwind.config = {
              theme: {
                  extend: {
                      colors: {
                          'flow-purple': '#8B5CF6',
                          'flow-blue': '#1E3A8A',
                          'flow-dark': '#0F172A'
                      },
                      backdropBlur: {
                          '20': '20px'
                      }
                  }
              }
          }
      </script>

      <!-- Custom Font -->
      <link rel="preconnect" href="https://fonts.googleapis.com">
      <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
      <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

      <style>
          /* Dark Gradient Design System - Matching Mockups */
          :root {
              --flow-bg-gradient: linear-gradient(135deg, #2D1B69 0%, #0F0C29 100%);
              --flow-purple-primary: #6B46C1;
              --flow-purple-accent: #8B5CF6;
              --flow-card-bg: rgba(255, 255, 255, 0.08);
              --flow-card-border: rgba(255, 255, 255, 0.1);
              --flow-glass: rgba(255, 255, 255, 0.05);
              --flow-text: #FFFFFF;
              --flow-text-secondary: #B8BCC8;
              --flow-text-muted: #7C7F87;
              --flow-success: #4ADE80;
              --flow-warning: #FBBF24;
              --flow-critical: #F87171;
          }

          * {
              font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
              box-sizing: border-box;
          }

          body {
              background: var(--flow-bg-gradient);
              background-attachment: fixed;
              min-height: 100vh;
              margin: 0;
              padding: 0;
              color: var(--flow-text);
              overflow-x: hidden;
          }

          /* Desktop Layout Container */
          .desktop-container {
              max-width: 1200px;
              margin: 0 auto;
              padding: 40px 20px;
          }

          /* Glass Card Components */
          .glass-card {
              background: var(--flow-card-bg);
              backdrop-filter: blur(12px);
              -webkit-backdrop-filter: blur(12px);
              border: 1px solid var(--flow-card-border);
              border-radius: 20px;
              box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
          }

          .section-card {
              background: rgba(255, 255, 255, 0.06);
              backdrop-filter: blur(10px);
              -webkit-backdrop-filter: blur(10px);
              border: 1px solid rgba(255, 255, 255, 0.08);
              border-radius: 24px;
              padding: 32px;
              margin-bottom: 32px;
          }

          /* Circle Section - Journey Cards */
          .circle-header {
              display: flex;
              align-items: center;
              justify-content: space-between;
              margin-bottom: 24px;
          }

          .section-icon-circle {
              width: 24px;
              height: 24px;
              border: 1.5px solid #9CA3AF;
              border-radius: 50%;
              display: inline-block;
              margin-right: 16px;
          }

          .section-icon-pulse {
              width: 24px;
              height: 24px;
              margin-right: 16px;
              display: inline-flex;
              align-items: center;
              justify-content: center;
          }

          .pulse-icon {
              stroke: #60A5FA;
              stroke-width: 2;
          }

          .section-title {
              font-size: 24px;
              font-weight: 600;
              color: var(--flow-text);
              display: flex;
              align-items: center;
              letter-spacing: -0.02em;
          }

          .journey-card {
              background: rgba(255, 255, 255, 0.05);
              backdrop-filter: blur(8px);
              border: 1px solid rgba(255, 255, 255, 0.08);
              border-radius: 16px;
              padding: 24px;
              cursor: pointer;
              transition: all 0.2s ease;
              margin-bottom: 16px;
          }

          .journey-card:hover {
              background: rgba(255, 255, 255, 0.08);
              transform: translateY(-2px);
              box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
          }

          /* Empty State Styles */
          .empty-circle {
              display: flex;
              flex-direction: column;
              align-items: center;
              justify-content: center;
              padding: 60px 0;
              text-align: center;
          }

          .empty-circle-icon {
              width: 120px;
              height: 120px;
              border: 3px solid rgba(255, 255, 255, 0.2);
              border-radius: 50%;
              margin-bottom: 24px;
          }

          .empty-title {
              font-size: 20px;
              font-weight: 500;
              color: var(--flow-text);
              margin-bottom: 8px;
          }

          .empty-subtitle {
              font-size: 14px;
              color: var(--flow-text-secondary);
              margin-bottom: 24px;
          }

          /* Stacks Section - Moment Feed */
          .stacks-empty {
              display: flex;
              flex-direction: column;
              align-items: center;
              justify-content: center;
              padding: 60px 0;
              text-align: center;
          }

          .pulse-line {
              width: 60px;
              height: 2px;
              background: linear-gradient(90deg, transparent, var(--flow-purple-accent), transparent);
              margin: 0 auto 24px;
              animation: pulse 2s ease-in-out infinite;
          }

          @keyframes pulse {
              0%, 100% { opacity: 0.3; transform: scaleX(0.5); }
              50% { opacity: 1; transform: scaleX(1); }
          }

          /* Buttons */
          .btn-primary {
              background: linear-gradient(135deg, var(--flow-purple-primary), var(--flow-purple-accent));
              color: white;
              border: none;
              padding: 12px 24px;
              border-radius: 12px;
              font-weight: 600;
              font-size: 14px;
              cursor: pointer;
              transition: all 0.2s ease;
              box-shadow: 0 4px 16px rgba(107, 70, 193, 0.3);
          }

          .btn-primary:hover {
              transform: translateY(-2px);
              box-shadow: 0 6px 20px rgba(107, 70, 193, 0.4);
          }

          .btn-secondary {
              background: rgba(255, 255, 255, 0.1);
              color: var(--flow-text);
              border: 1px solid rgba(255, 255, 255, 0.2);
              padding: 12px 24px;
              border-radius: 12px;
              font-weight: 600;
              font-size: 14px;
              cursor: pointer;
              transition: all 0.2s ease;
          }

          .btn-secondary:hover {
              background: rgba(255, 255, 255, 0.15);
              border-color: rgba(255, 255, 255, 0.3);
          }

          /* Navigation Bar */
          .top-nav {
              display: flex;
              align-items: center;
              justify-content: space-between;
              margin-bottom: 40px;
          }

          .logo {
              font-size: 18px;
              font-weight: 600;
              color: var(--flow-text);
          }

          .nav-subtitle {
              font-size: 14px;
              color: var(--flow-text-secondary);
              font-weight: 400;
          }

          .nav-actions {
              display: flex;
              align-items: center;
              gap: 16px;
          }

          .icon-btn {
              width: 40px;
              height: 40px;
              display: flex;
              align-items: center;
              justify-content: center;
              background: rgba(255, 255, 255, 0.08);
              border: 1px solid rgba(255, 255, 255, 0.1);
              border-radius: 12px;
              cursor: pointer;
              transition: all 0.2s ease;
          }

          .icon-btn:hover {
              background: rgba(255, 255, 255, 0.12);
              border-color: rgba(255, 255, 255, 0.2);
          }

          /* Status Indicator */
          .status-dot {
              width: 8px;
              height: 8px;
              border-radius: 50%;
              display: inline-block;
          }

          .status-green {
              background: var(--flow-success);
          }

          .status-warning {
              background: var(--flow-warning);
          }

          .status-critical {
              background: var(--flow-critical);
          }

          /* Journey Status Pills */
          .journey-meta {
              display: flex;
              align-items: center;
              gap: 20px;
              margin-top: 12px;
              font-size: 14px;
              color: #B8BCC8;
          }

          .journey-meta-item {
              display: flex;
              align-items: center;
              gap: 6px;
          }

          .journey-meta-item svg {
              opacity: 0.7;
              flex-shrink: 0;
          }
      </style>
  </head>
  <body>
      <div class="desktop-container">
          <!-- Top Navigation Bar -->
          <nav class="top-nav">
              <div>
                  <div class="logo">FlowJM</div>
                  <div class="nav-subtitle">Journey Management</div>
              </div>
              <div class="nav-actions">
                  <div class="icon-btn">
                      <svg width="20" height="20" fill="none" stroke="currentColor" strokeWidth="2">
                          <circle cx="10" cy="10" r="7"/>
                      </svg>
                  </div>
                  <button class="icon-btn">
                      <svg width="20" height="20" fill="none" stroke="currentColor" strokeWidth="2">
                          <path d="M12 2v20M2 12h20"/>
                      </svg>
                  </button>
              </div>
          </nav>

          <!-- Main Title Section -->
          <div class="text-center mb-12">
              <h1 class="text-4xl font-semibold mb-2">The Lookout</h1>
              <p class="text-gray-400">Your Flow command center</p>
          </div>

          <!-- Circle Section -->
          <section class="section-card">
              <div class="circle-header">
                  <h2 class="section-title">
                      <span class="section-icon-circle"></span>
                      Circle
                  </h2>
                  <button class="btn-primary" onclick="createJourney()">
                      + New Journey
                  </button>
              </div>

              <?php if (!empty($circleJourneys)): ?>
                  <?php foreach ($circleJourneys as $circleJourney): ?>
                  <div class="journey-card" onclick="viewJourney(<?php echo $circleJourney['id']; ?>)">
                      <div class="flex justify-between items-start">
                          <div class="flex-1">
                              <h3 class="text-lg font-semibold mb-1"><?php echo escapeContent($circleJourney['title']); ?></h3>
                              <p class="text-sm text-gray-400 mb-3"><?php echo escapeContent($circleJourney['client_name'] ?? 'Personal Project'); ?></p>
                          </div>
                          <span class="status-dot <?php
                              echo $circleJourney['pulse_status'] == 'critical' ? 'status-critical' :
                                  ($circleJourney['pulse_status'] == 'warning' ? 'status-warning' : 'status-green');
                          ?>"></span>
                      </div>
                      <div class="journey-meta">
                          <div class="journey-meta-item">
                              <svg width="18" height="18" fill="none" stroke="currentColor" strokeWidth="1.5" style="opacity: 0.6;">
                                  <circle cx="9" cy="9" r="7"/>
                                  <path d="M9 5v4l2.5 2.5"/>
                              </svg>
                              <span>Due <?php echo date('M j', strtotime($circleJourney['target_date'] ?? '+7 days')); ?></span>
                          </div>
                          <div class="journey-meta-item">
                              <svg width="18" height="18" fill="none" stroke="currentColor" strokeWidth="1.5" style="opacity: 0.6;">
                                  <path d="M9 3v12M6 6h4.5a2.5 2.5 0 0 1 0 5c1.5 0 2.5 1 2.5 2.5s-1 2.5-2.5 2.5H6"/>
                              </svg>
                              <span>$<?php echo number_format($circleJourney['balance_due'] ?? 0, 0, '.', ','); ?></span>
                          </div>
                          <div class="journey-meta-item">
                              <svg width="18" height="18" fill="none" stroke="currentColor" strokeWidth="1.5" style="opacity: 0.6;">
                                  <path d="M3 9h3l2-5 4 10 2-5h3"/>
                              </svg>
                              <span><?php echo $circleJourney['moment_count'] ?? 0; ?> moments</span>
                          </div>
                      </div>
                  </div>
                  <?php endforeach; ?>
              <?php else: ?>
                  <div class="empty-circle">
                      <div class="empty-circle-icon"></div>
                      <div class="empty-title">Your Circle awaits</div>
                      <div class="empty-subtitle">Start your first journey to see it here</div>
                      <button class="btn-secondary" onclick="createJourney()">
                          Create Journey
                      </button>
                  </div>
              <?php endif; ?>
          </section>

          <!-- Stacks Section -->
          <section class="section-card">
              <div class="circle-header">
                  <h2 class="section-title">
                      <span class="section-icon-pulse">
                          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" class="pulse-icon">
                              <path d="M3 12h4l3-9 4 18 3-9h4" stroke="currentColor"/>
                          </svg>
                      </span>
                      Stacks
                  </h2>
                  <button class="btn-primary" onclick="openQuickAdd()">
                      + Add Moment
                  </button>
              </div>

              <?php if (!empty($stackMoments)): ?>
                  <?php foreach ($stackMoments as $moment): ?>
                  <div class="journey-card" data-moment-id="<?php echo $moment['id']; ?>">
                      <div class="flex justify-between items-start mb-2">
                          <span class="text-xs font-semibold text-purple-400 uppercase tracking-wide">
                              <?php echo escapeContent($moment['journey_title'] ?? 'Untitled Journey'); ?>
                          </span>
                          <span class="text-xs text-gray-400">
                              <?php echo time_ago($moment['created_at']); ?>
                          </span>
                      </div>
                      <p class="text-gray-200 leading-relaxed">
                          <?php echo escapeContent($moment['content']); ?>
                      </p>
                      <?php if (!empty($moment['type']) && $moment['type'] != 'update'): ?>
                      <div class="mt-3">
                          <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full
                              <?php echo $moment['type'] == 'milestone' ? 'bg-green-500/20 text-green-400' : ''; ?>
                              <?php echo $moment['type'] == 'blocker' ? 'bg-red-500/20 text-red-400' : ''; ?>
                              <?php echo $moment['type'] == 'note' ? 'bg-gray-500/20 text-gray-400' : ''; ?>">
                              <?php echo ucfirst($moment['type']); ?>
                          </span>
                      </div>
                      <?php endif; ?>
                  </div>
                  <?php endforeach; ?>
              <?php else: ?>
                  <div class="stacks-empty">
                      <div class="pulse-line"></div>
                      <div class="empty-title">No moments yet</div>
                      <div class="empty-subtitle">Capture your first moment to start building your Stack</div>
                      <button class="btn-secondary" onclick="openQuickAdd()" style="margin-top: 24px;">
                          Add Moment
                      </button>
                  </div>
              <?php endif; ?>

              <!-- View Camp Button at bottom of Stacks -->
              <div style="margin-top: 32px; padding-top: 24px; border-top: 1px solid rgba(255,255,255,0.08);">
                  <button class="btn-secondary w-full" onclick="viewFullCamp()" style="width: 100%; padding: 16px; font-size: 16px;">
                      🏕 View Camp
                  </button>
              </div>
          </section>
      </div>

      <!-- Quick Add Modal -->
      <div id="quick-add-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center;">
          <div class="glass-card" style="width: 90%; max-width: 500px; padding: 32px;">
              <h3 class="text-xl font-semibold mb-6">Log a Moment</h3>
              <textarea id="moment-content" class="w-full p-4" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white; min-height: 120px;" placeholder="What progress did you
  make?"></textarea>
              <select id="journey-select" class="w-full mt-4 p-4" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white;">
                  <option value="">Select Journey</option>
                  <?php foreach ($activeJourneys as $j): ?>
                  <option value="<?php echo $j['id']; ?>"><?php echo escapeContent($j['title']); ?></option>
                  <?php endforeach; ?>
              </select>
              <div class="flex gap-4 mt-6">
                  <button onclick="closeQuickAdd()" class="btn-secondary flex-1">Cancel</button>
                  <button onclick="saveMoment()" class="btn-primary flex-1">Save Moment</button>
              </div>
          </div>
      </div>

      <script>
      // Quick Add Functions
      function openQuickAdd() {
          document.getElementById('quick-add-modal').style.display = 'flex';
          setTimeout(() => {
              document.getElementById('moment-content').focus();
          }, 100);
      }

      function closeQuickAdd() {
          document.getElementById('quick-add-modal').style.display = 'none';
      }

      // Navigation Functions
      function viewJourney(id) {
          window.location.href = `/journey.php?id=${id}`;
      }

      function createJourney() {
          window.location.href = '/journey/create.php';
      }

      function viewFullCamp() {
          window.location.href = '/camp.php';
      }

      function viewJourneys() {
          window.location.href = '/journeys.php';
      }

      function viewProfile() {
          window.location.href = '/profile.php';
      }

      // Save Moment
      function saveMoment() {
          const content = document.getElementById('moment-content').value;
          const journeyId = document.getElementById('journey-select').value;

          if (!content || !journeyId) {
              alert('Please enter content and select a journey');
              return;
          }

          // TODO: Implement AJAX save
          console.log('Saving moment:', { content, journeyId });
          closeQuickAdd();
      }
      </script>
  </body>
  </html>