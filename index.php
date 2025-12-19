<?php
// Simple configuration and user preferences

// Default settings
$default_theme = 'blue'; // available: blue, red, yellow, black
$default_cse_id = 'c744495a684ef426f';

// Fixed navbar links (cannot be changed)
$fixed_nav_links = [
    ['label' => 'поисковик', 'url' => 'index.php'],
    ['label' => 'feed', 'url' => 'feed.php'],
];

// Read current settings from cookies or fall back to defaults
$current_theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : $default_theme;
$current_cse_id = isset($_COOKIE['cse_id']) ? $_COOKIE['cse_id'] : $default_cse_id;

// Read custom navbar links from cookies (JSON)
$custom_links_json = isset($_COOKIE['custom_nav_links']) ? $_COOKIE['custom_nav_links'] : '[]';
$custom_links = json_decode($custom_links_json, true);
if (!is_array($custom_links)) {
    $custom_links = [];
}

// Combine fixed and custom links
$current_nav_links = array_merge($fixed_nav_links, $custom_links);

// Handle settings form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_theme = isset($_POST['theme']) ? $_POST['theme'] : $current_theme;
    $new_cse_id = isset($_POST['cse_id']) ? trim($_POST['cse_id']) : $current_cse_id;

    // Allow only known themes
    $allowed_themes = ['blue', 'red', 'yellow', 'black'];
    if (!in_array($new_theme, $allowed_themes, true)) {
        $new_theme = $default_theme;
    }

    // If CSE ID is empty, revert to default
    if ($new_cse_id === '') {
        $new_cse_id = $default_cse_id;
    }

    // Handle clearing all links
    if (isset($_POST['clear_links']) && $_POST['clear_links'] == '1') {
        $new_custom_links = [];
    } else {
        // Handle custom links - add new or update existing
        $new_custom_links = [];
        if (isset($_POST['custom_links']) && is_array($_POST['custom_links'])) {
            foreach ($_POST['custom_links'] as $link) {
                $label = isset($link['label']) ? trim($link['label']) : '';
                $url = isset($link['url']) ? trim($link['url']) : '';
                if ($label !== '' && $url !== '') {
                    $new_custom_links[] = ['label' => $label, 'url' => $url];
                }
            }
        }

        // Handle adding new link
        if (isset($_POST['add_link_label']) && isset($_POST['add_link_url'])) {
            $new_label = trim($_POST['add_link_label']);
            $new_url = trim($_POST['add_link_url']);
            if ($new_label !== '' && $new_url !== '') {
                $new_custom_links[] = ['label' => $new_label, 'url' => $new_url];
            }
        }
    }

    // Store preferences in cookies for 30 days
    setcookie('theme', $new_theme, time() + 60 * 60 * 24 * 30, '/');
    setcookie('cse_id', $new_cse_id, time() + 60 * 60 * 24 * 30, '/');
    setcookie('custom_nav_links', json_encode($new_custom_links), time() + 60 * 60 * 24 * 30, '/');

    // Update current values for this request
    $current_theme = $new_theme;
    $current_cse_id = $new_cse_id;
    $custom_links = $new_custom_links;
    $current_nav_links = array_merge($fixed_nav_links, $custom_links);

    // Simple redirect to avoid form resubmission on refresh
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

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
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Blink</title>
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #fff;
        }

        /* Theme: полностью меняем фон навбара на градиент */
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
            padding: 15px 15px 40px; /* extra bottom padding for footer link */
            overflow-y: auto;
            position: fixed;
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

        .btn {
            border: none !important;
        }

        .custom-link-item {
            background-color: #111;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 4px;
            border: 1px solid #333;
        }

        .custom-link-item label {
            display: block;
            margin-bottom: 5px;
            font-size: 12px;
        }

        .custom-link-item .form-control {
            margin-bottom: 5px;
        }

        .add-link-section {
            background-color: #111;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 4px;
            border: 1px solid #333;
        }

        .settings-footer-link {
            position: absolute;
            left: 15px;
            bottom: 10px;
            font-size: 12px;
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
                    <span class="sr-only">Переключить навигацию</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
            </div>
            <div class="collapse navbar-collapse" id="navcol-1">
                <ul class="nav navbar-nav">
                    <?php foreach ($current_nav_links as $index => $link): ?>
                        <li class="<?php echo ($link['url'] === 'index.php' || $link['url'] === basename($_SERVER['PHP_SELF'])) ? 'active' : ''; ?>">
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
        <h2>Настройки</h2>
        <form method="post" id="settings-form">
            <div class="form-group">
                <label for="theme">Цветовая схема навбара:</label>
                <select id="theme" name="theme" class="form-control">
                    <option value="blue" <?php echo $current_theme === 'blue' ? 'selected' : ''; ?>>Синий (по умолчанию)</option>
                    <option value="red" <?php echo $current_theme === 'red' ? 'selected' : ''; ?>>Красный</option>
                    <option value="yellow" <?php echo $current_theme === 'yellow' ? 'selected' : ''; ?>>Жёлтый</option>
                    <option value="black" <?php echo $current_theme === 'black' ? 'selected' : ''; ?>>Чёрный</option>
                </select>
            </div>

            <hr>

            <div class="form-group">
                <label for="cse_id">Google CSE ID (cx):</label>
                <input type="text"
                       id="cse_id"
                       name="cse_id"
                       class="form-control"
                       placeholder="например: c744495a684ef426f"
                       value="<?php echo htmlspecialchars($current_cse_id, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <hr>

            <h3 style="font-size: 16px; color: #fff; margin-bottom: 10px;">Дополнительные ссылки</h3>
            <p style="font-size: 12px; color: #999;">Кнопки "поисковик" и "feed" нельзя изменить</p>

            <div id="custom-links-container">
                <?php foreach ($custom_links as $index => $link): ?>
                    <div class="custom-link-item" data-index="<?php echo $index; ?>">
                        <label>Текст:</label>
                        <input type="text"
                               name="custom_links[<?php echo $index; ?>][label]"
                               class="form-control"
                               value="<?php echo htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8'); ?>"
                               required>
                        <label>URL:</label>
                        <input type="text"
                               name="custom_links[<?php echo $index; ?>][url]"
                               class="form-control"
                               value="<?php echo htmlspecialchars($link['url'], ENT_QUOTES, 'UTF-8'); ?>"
                               required>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="add-link-section">
                <h4 style="font-size: 14px; color: #fff; margin-bottom: 10px;">Добавить ссылку</h4>
                <div class="form-group">
                    <label>Текст:</label>
                    <input type="text"
                           id="add_link_label"
                           name="add_link_label"
                           class="form-control"
                           placeholder="Название ссылки">
                </div>
                <div class="form-group">
                    <label>URL:</label>
                    <input type="text"
                           id="add_link_url"
                           name="add_link_url"
                           class="form-control"
                           placeholder="https://example.com">
                </div>
            </div>

            <?php if (!empty($custom_links)): ?>
                <button type="button" id="clear-links-btn" class="btn btn-danger btn-block" style="margin-bottom: 10px;">Очистить все</button>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary btn-block">Сохранить</button>
        </form>
        <a href="https://github.com/yourusername/blink"
           target="_blank"
           class="settings-footer-link"
           style="color: #ccc; text-decoration: none;">
            source code
        </a>
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
                    <p>Вы уверены, что хотите удалить все дополнительные ссылки?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
                    <button type="button" class="btn btn-danger" id="clear-confirm-btn">Очистить</button>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="col-md-12">
            <script async src="https://cse.google.com/cse.js?cx=<?php echo htmlspecialchars($current_cse_id, ENT_QUOTES, 'UTF-8'); ?>"></script>
            <div class="gcse-search"></div>
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

                // Clear all links button handler
                $(document).on('click', '#clear-links-btn', function (e) {
                    e.preventDefault();
                    $('#clear-confirm-modal').modal('show');
                });

                $('#clear-confirm-btn').on('click', function () {
                    $('#custom-links-container').empty();
                    $('#settings-form').append('<input type="hidden" name="clear_links" value="1">');
                    $('#settings-form').submit();
                });

                // Re-index custom links before submit
                $('#settings-form').on('submit', function () {
                    // Remove hidden clear_links input if exists
                    $('input[name="clear_links"]').remove();
                    
                    // Re-index remaining links
                    $('#custom-links-container .custom-link-item').each(function (index) {
                        $(this).find('input[name^="custom_links"]').each(function () {
                            var name = $(this).attr('name');
                            name = name.replace(/custom_links\[\d+\]/, 'custom_links[' + index + ']');
                            $(this).attr('name', name);
                        });
                    });
                });

            });
        })(jQuery);
    </script>
</body>
</html>
