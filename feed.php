<?php
// RSS Feed Reader - Blink Feed

$default_theme = 'blue';
$default_feed_url = 'https://multura.serv00.net/feeds/blinkfeed/';

// Read theme from cookies
$current_theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : $default_theme;

// Map theme to primary color and darker shade for gradient
switch ($current_theme) {
    case 'red':
        $primary_color = '#ff4d4d';
        $primary_color_dark = '#b30000';
        break;
    case 'yellow':
        $primary_color = '#ffd74d';
        $primary_color_dark = '#b38600';
        break;
    case 'black':
        $primary_color = '#555555';
        $primary_color_dark = '#000000';
        break;
    case 'blue':
    default:
        $primary_color = '#4f93ce';
        $primary_color_dark = '#285f8f';
        break;
}

// Fixed navbar links (same as index.php)
$fixed_nav_links = [
    ['label' => 'поисковик', 'url' => 'index.php'],
    ['label' => 'feed', 'url' => 'feed.php'],
];

// Read custom navbar links from cookies (JSON)
$custom_links_json = isset($_COOKIE['custom_nav_links']) ? $_COOKIE['custom_nav_links'] : '[]';
$custom_links = json_decode($custom_links_json, true);
if (!is_array($custom_links)) {
    $custom_links = [];
}

// Combine fixed and custom links
$current_nav_links = array_merge($fixed_nav_links, $custom_links);

// Read custom feeds from cookies (JSON)
$custom_feeds_json = isset($_COOKIE['custom_feeds']) ? $_COOKIE['custom_feeds'] : '[]';
$custom_feeds = json_decode($custom_feeds_json, true);
if (!is_array($custom_feeds)) {
    $custom_feeds = [];
}

// Handle settings form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle clearing all feeds
    if (isset($_POST['clear_feeds']) && $_POST['clear_feeds'] == '1') {
        $new_custom_feeds = [];
    } else {
        // Handle custom feeds - add new or update existing
        $new_custom_feeds = [];
        if (isset($_POST['custom_feeds']) && is_array($_POST['custom_feeds'])) {
            foreach ($_POST['custom_feeds'] as $feed) {
                $url = isset($feed['url']) ? trim($feed['url']) : '';
                $name = isset($feed['name']) ? trim($feed['name']) : '';
                if ($url !== '') {
                    $new_custom_feeds[] = [
                        'url' => $url,
                        'name' => $name !== '' ? $name : $url
                    ];
                }
            }
        }

        // Handle adding new feed
        if (isset($_POST['add_feed_url']) && isset($_POST['add_feed_name'])) {
            $new_url = trim($_POST['add_feed_url']);
            $new_name = trim($_POST['add_feed_name']);
            if ($new_url !== '') {
                $new_custom_feeds[] = [
                    'url' => $new_url,
                    'name' => $new_name !== '' ? $new_name : $new_url
                ];
            }
        }
    }

    // Store preferences in cookies for 30 days
    setcookie('custom_feeds', json_encode($new_custom_feeds), time() + 60 * 60 * 24 * 30, '/');

    // Update current values for this request
    $custom_feeds = $new_custom_feeds;

    // Simple redirect to avoid form resubmission on refresh
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

// Get feed URL from query or cookie
$feed_url = isset($_GET['url']) ? $_GET['url'] : (isset($_COOKIE['feed_url']) ? $_COOKIE['feed_url'] : $default_feed_url);

// Simple RSS parser
function parseRSS($url) {
    $xml = @simplexml_load_file($url);
    if ($xml === false) {
        return ['error' => 'Не удалось загрузить RSS ленту'];
    }
    
    $items = [];
    if (isset($xml->channel->item)) {
        foreach ($xml->channel->item as $item) {
            $items[] = [
                'title' => (string)$item->title,
                'link' => (string)$item->link,
                'description' => isset($item->description) ? (string)$item->description : '',
                'pubDate' => isset($item->pubDate) ? (string)$item->pubDate : '',
            ];
        }
    }
    
    return [
        'title' => isset($xml->channel->title) ? (string)$xml->channel->title : 'RSS Feed',
        'description' => isset($xml->channel->description) ? (string)$xml->channel->description : '',
        'items' => $items,
    ];
}

$feed_data = parseRSS($feed_url);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Blink Feed</title>
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #fff;
        }

        .navbar-inverse {
            position: relative;
            z-index: 1050; /* выше панели настроек */
            background: linear-gradient(
                    to bottom,
                    <?php echo htmlspecialchars($primary_color, ENT_QUOTES, 'UTF-8'); ?> 0%,
                    <?php echo htmlspecialchars($primary_color_dark, ENT_QUOTES, 'UTF-8'); ?> 100%
            );
            border-color: <?php echo htmlspecialchars($primary_color_dark, ENT_QUOTES, 'UTF-8'); ?>;
        }

        .navbar-inverse .navbar-brand,
        .navbar-inverse .navbar-nav > li > a {
            color: #ffffff;
        }

        .navbar-inverse .navbar-nav > li > a:hover,
        .navbar-inverse .navbar-nav > li > a:focus {
            color: #f5f5f5;
            background-color: rgba(0, 0, 0, 0.15);
        }

        .navbar-inverse .navbar-nav > .active > a,
        .navbar-inverse .navbar-nav > .active > a:focus,
        .navbar-inverse .navbar-nav > .active > a:hover {
            color: #ffffff;
            background-color: rgba(0, 0, 0, 0.2);
        }

        /* Right-side dark settings drawer - starts below navbar */
        .settings-drawer {
            position: fixed;
            top: 50px; /* Start below navbar */
            right: 0;
            width: 320px;
            max-width: 90%;
            height: calc(100% - 50px); /* Full height minus navbar */
            background: #000; /* Completely black */
            color: #ddd;
            box-shadow: -2px 0 8px rgba(0, 0, 0, 0.5);
            transform: translateX(100%);
            transition: transform 0.3s ease-in-out;
            z-index: 1040;
            padding: 15px;
            overflow-y: auto;
        }

        .settings-drawer.open {
            transform: translateX(0);
        }

        .settings-drawer h2 {
            position: sticky;
            top: 0;
            z-index: 1;
            background-color: #000;
            padding-bottom: 10px;
            font-size: 18px;
            margin-top: 0;
            margin-bottom: 15px;
            color: #fff;
            border-bottom: 1px solid #333;
        }

        .settings-drawer h3 {
            font-size: 16px;
            color: #fff;
            margin-bottom: 10px;
        }

        .settings-drawer label,
        .settings-drawer p {
            font-weight: normal;
            color: #ccc;
        }

        .settings-drawer .form-control {
            background-color: #222;
            border-color: #444;
            color: #eee;
        }

        .settings-drawer .form-control:focus {
            border-color: <?php echo htmlspecialchars($primary_color, ENT_QUOTES, 'UTF-8'); ?>;
            box-shadow: none;
            background-color: #333;
        }

        .settings-drawer .btn-primary {
            background-color: <?php echo htmlspecialchars($primary_color, ENT_QUOTES, 'UTF-8'); ?>;
            border: none;
            color: #fff;
        }

        .settings-drawer .btn-danger {
            background-color: #d9534f;
            border: none;
            color: #fff;
        }

        .custom-feed-item {
            background-color: #111;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 4px;
            border: 1px solid #333;
        }

        .custom-feed-item label {
            display: block;
            margin-bottom: 5px;
            font-size: 12px;
        }

        .custom-feed-item .form-control {
            margin-bottom: 5px;
        }

        .add-feed-section {
            background-color: #111;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 4px;
            border: 1px solid #333;
        }

        .feed-item {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #f9f9f9;
        }

        .feed-item h3 {
            margin-top: 0;
        }

        .feed-item .pub-date {
            color: #666;
            font-size: 0.9em;
        }

        .feed-item a {
            color: <?php echo htmlspecialchars($primary_color_dark, ENT_QUOTES, 'UTF-8'); ?>;
        }

        .feed-item a:hover,
        .feed-item a:focus {
            color: <?php echo htmlspecialchars($primary_color, ENT_QUOTES, 'UTF-8'); ?>;
            text-decoration: none;
        }

        .feed-header h2 {
            color: <?php echo htmlspecialchars($primary_color_dark, ENT_QUOTES, 'UTF-8'); ?>;
        }

        .feed-url-form .btn-primary {
            background-color: <?php echo htmlspecialchars($primary_color, ENT_QUOTES, 'UTF-8'); ?>;
            border: none;
        }

        .btn {
            border: none !important;
        }

        .feed-selector {
            margin-bottom: 20px;
        }

        .feed-selector .btn {
            margin-right: 5px;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-inverse">
        <div class="container-fluid">
            <div class="navbar-header">
                <a class="navbar-brand" href="index.php">
                    <img src="assets/img/logo.svg" width="72" height="21" alt="Blink">
                </a>
                <button data-toggle="collapse" class="navbar-toggle collapsed" data-target="#navcol-1">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
            </div>
            <div class="collapse navbar-collapse" id="navcol-1">
                <ul class="nav navbar-nav">
                    <?php foreach ($current_nav_links as $link): ?>
                        <li class="<?php echo ($link['url'] === 'feed.php') ? 'active' : ''; ?>">
                            <a href="<?php echo htmlspecialchars($link['url'], ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <ul class="nav navbar-nav navbar-right">
                    <li>
                        <a href="#" id="settings-toggle" title="Настройки">
                            <i class="bi bi-gear-fill" aria-hidden="true"></i>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Right-side settings drawer -->
    <div class="settings-drawer" id="settings-drawer">
        <h2>Настройки фидов</h2>
        <form method="post" id="settings-form">
            <h3>Дополнительные фиды</h3>
            <p style="font-size: 12px; color: #999;">Добавьте RSS фиды для быстрого доступа</p>

            <div id="custom-feeds-container">
                <?php foreach ($custom_feeds as $index => $feed): ?>
                    <div class="custom-feed-item" data-index="<?php echo $index; ?>">
                        <label>Название:</label>
                        <input type="text"
                               name="custom_feeds[<?php echo $index; ?>][name]"
                               class="form-control"
                               value="<?php echo htmlspecialchars($feed['name'], ENT_QUOTES, 'UTF-8'); ?>"
                               required>
                        <label>URL:</label>
                        <input type="text"
                               name="custom_feeds[<?php echo $index; ?>][url]"
                               class="form-control"
                               value="<?php echo htmlspecialchars($feed['url'], ENT_QUOTES, 'UTF-8'); ?>"
                               required>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="add-feed-section">
                <h4 style="font-size: 14px; color: #fff; margin-bottom: 10px;">Добавить фид</h4>
                <div class="form-group">
                    <label>Название:</label>
                    <input type="text"
                           id="add_feed_name"
                           name="add_feed_name"
                           class="form-control"
                           placeholder="Название фида">
                </div>
                <div class="form-group">
                    <label>URL:</label>
                    <input type="text"
                           id="add_feed_url"
                           name="add_feed_url"
                           class="form-control"
                           placeholder="https://example.com/feed.xml">
                </div>
            </div>

            <?php if (!empty($custom_feeds)): ?>
                <button type="button" id="clear-feeds-btn" class="btn btn-danger btn-block" style="margin-bottom: 10px;">Очистить все</button>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary btn-block">Сохранить</button>
        </form>
    </div>

    <!-- Bootstrap 3 Modal for clear confirmation -->
    <div class="modal fade" id="clear-confirm-modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title">Подтверждение</h4>
                </div>
                <div class="modal-body">
                    <p>Вы уверены, что хотите удалить все дополнительные фиды?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
                    <button type="button" class="btn btn-danger" id="clear-confirm-btn">Очистить</button>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="feed-selector">
                    <?php if (!empty($custom_feeds)): ?>
                        <?php foreach ($custom_feeds as $feed): ?>
                            <a href="?url=<?php echo urlencode($feed['url']); ?>" class="btn btn-primary">
                                <?php echo htmlspecialchars($feed['name'], ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php if (isset($feed_data['error'])): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($feed_data['error'], ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php else: ?>
                    <div class="feed-header">
                        <h2><?php echo htmlspecialchars($feed_data['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                        <?php if ($feed_data['description']): ?>
                            <p><?php echo htmlspecialchars($feed_data['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endif; ?>
                    </div>

                    <?php if (empty($feed_data['items'])): ?>
                        <p>Нет элементов в ленте.</p>
                    <?php else: ?>
                        <?php foreach ($feed_data['items'] as $item): ?>
                            <div class="feed-item">
                                <h3>
                                    <a href="<?php echo htmlspecialchars($item['link'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank">
                                        <?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?>
                                    </a>
                                </h3>
                                <?php if ($item['pubDate']): ?>
                                    <div class="pub-date">
                                        <?php echo htmlspecialchars($item['pubDate'], ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($item['description']): ?>
                                    <div class="description">
                                        <?php echo htmlspecialchars($item['description'], ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/bootstrap/js/bootstrap.min.js"></script>
    <script>
        (function ($) {
            $(function () {
                $('#settings-toggle').on('click', function (e) {
                    e.preventDefault();
                    $('#settings-drawer').toggleClass('open');
                });

                // Clear all feeds button handler
                $(document).on('click', '#clear-feeds-btn', function (e) {
                    e.preventDefault();
                    $('#clear-confirm-modal').modal('show');
                });

                $('#clear-confirm-btn').on('click', function () {
                    $('#custom-feeds-container').empty();
                    $('#settings-form').append('<input type="hidden" name="clear_feeds" value="1">');
                    $('#settings-form').submit();
                });

                // Re-index custom feeds before submit
                $('#settings-form').on('submit', function () {
                    // Remove hidden clear_feeds input if exists
                    $('input[name="clear_feeds"]').remove();
                    
                    // Re-index remaining feeds
                    $('#custom-feeds-container .custom-feed-item').each(function (index) {
                        $(this).find('input[name^="custom_feeds"]').each(function () {
                            var name = $(this).attr('name');
                            name = name.replace(/custom_feeds\[\d+\]/, 'custom_feeds[' + index + ']');
                            $(this).attr('name', name);
                        });
                    });
                });

            });
        })(jQuery);
    </script>
</body>
</html>
