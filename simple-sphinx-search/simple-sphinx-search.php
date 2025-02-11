<?php
/**
 * Plugin Name: Simple Sphinx Search
 * Plugin URI: https://pravdaurfo.ru
 * Description: Простая и эффективная поисковая система на базе Sphinx
 * Plugin Tags: sphinx, search, plugin
 * Version: 1.0.0
 * Author: Alex Elph
 * Text Domain: simple-sphinx-search
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package Simple_Sphinx_Search
 */

/**
 * Защита от прямого доступа к файлу
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Определяем константы плагина
 */
define( 'SPHINX_SEARCH_VERSION', '1.0.0' );
define( 'SPHINX_SEARCH_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SPHINX_SEARCH_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Функция активации плагина
 */
function sphinx_search_activate() {
	$default_options = array(
		'sphinx_host'        => '127.0.0.1',
		'sphinx_port'        => '9306',
		'sphinx_index'       => '',
		'show_in_menu'       => '1',
		'show_in_settings'   => '1',
		'show_in_admin_bar'  => '1',
		'menu_position'      => '2',
		'admin_bar_position' => '500',
	);
	add_option( 'sphinx_search_options', $default_options );
}
register_activation_hook( __FILE__, 'sphinx_search_activate' );

/**
 * Функция деактивации плагина
 */
function sphinx_search_deactivate() {
	delete_option( 'sphinx_search_options' );
}
register_deactivation_hook( __FILE__, 'sphinx_search_deactivate' );

/**
 * Инициализация плагина
 */
function sphinx_search_init() {
	load_plugin_textdomain( 'simple-sphinx-search', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	wp_enqueue_style( 'sphinx-search-style', SPHINX_SEARCH_PLUGIN_URL . 'css/style.css', array(), SPHINX_SEARCH_VERSION );
}
add_action( 'init', 'sphinx_search_init' );

/**
 * Добавляем пункт меню в настройки
 */
function sphinx_search_add_admin_menu() {
    // Добавляем пункт в меню Настройки
    $options = get_option('sphinx_search_options');
    if (!isset($options['show_in_settings']) || $options['show_in_settings']) {
        add_options_page(
            'Настройки Sphinx Search',
            'Sphinx Search',
            'manage_options',
            'sphinx-search-settings',
            'sphinx_search_options_page'
        );
    }
}
add_action( 'admin_menu', 'sphinx_search_add_admin_menu' );

/**
 * Добавляем страницу поиска в админку
 */
function sphinx_search_add_search_page() {
    $options = get_option('sphinx_search_options');
    if (!isset($options['show_in_menu']) || $options['show_in_menu']) {
        // Добавляем главный пункт меню
        add_menu_page(
            'Поиск Sphinx',
            'Поиск Sphinx',
            'read',
            'sphinx-search',
            'sphinx_search_render_page',
            'dashicons-search',
            intval($options['menu_position'])
        );

        // Добавляем подпункт настроек
        add_submenu_page(
            'sphinx-search',
            'Настройки Sphinx',
            'Настройки',
            'manage_options',
            'sphinx-search-settings',
            'sphinx_search_options_page'
        );
    }
}
add_action('admin_menu', 'sphinx_search_add_search_page');

/**
 * Отображаем страницу поиска
 */
function sphinx_search_render_page() {
    if (!current_user_can('read')) {
        wp_die(__('Вам не разрешено просматривать эту страницу.'));
    }
    echo '<div class="wrap">';
    echo '<h1>Поиск Sphinx</h1>';
    echo sphinx_search_page();
    echo '</div>';
}

/**
 * Добавляем пункт в админ-бар
 */
function sphinx_search_admin_bar_menu( $wp_admin_bar ) {
    $options = get_option( 'sphinx_search_options' );
    if ( !isset( $options['show_in_admin_bar'] ) || $options['show_in_admin_bar'] ) {
        $wp_admin_bar->add_node( array(
            'id'     => 'sphinx-search',
            'title'  => 'Поиск',
            'href'   => admin_url( 'admin.php?page=sphinx-search' )
        ) );
    }
}

$options = get_option('sphinx_search_options');
$position = isset($options['admin_bar_position']) ? intval($options['admin_bar_position']) : 500;
add_action('admin_bar_menu', 'sphinx_search_admin_bar_menu', $position);

/**
 * Регистрируем настройки
 */
// Функция для вывода разделителя
function sphinx_search_section_separator() {
    echo '<hr>';
}

function sphinx_search_settings_init() {
	register_setting(
		'sphinx_search',
		'sphinx_search_options',
		[
			'sanitize_callback' => 'sphinx_search_validate_options'
		]
	);

	// Функция валидации опций
	function sphinx_search_validate_options($input) {
		// Если оба чекбокса отключены
		if (isset($input['show_in_menu']) && !$input['show_in_menu'] &&
			isset($input['show_in_settings']) && !$input['show_in_settings']) {
			
			// Сохраняем сообщение об ошибке
			add_settings_error(
				'sphinx_search',
				'menu_visibility',
				'Невозможно отключить оба пункта меню. Хотя бы один должен быть включен.',
				'error'
			);

			// Возвращаем старые настройки
			return get_option('sphinx_search_options');
		}

		return $input;
	}

	// Функция для кнопки сохранения
	function sphinx_search_save_connection() {
		submit_button('Сохранить изменения', 'primary', 'submit', false);
	}

	// Настройки подключения.
	add_settings_section(
		'sphinx_search_connection',
		'Настройки подключения',
		'sphinx_search_connection_callback',
		'sphinx-search'
	);

	// Секция проверки подключения
	add_settings_section(
		'sphinx_search_test',
		'Проверка подключения',
		null,
		'sphinx-search'
	);

	// Кнопка проверки подключения
	add_settings_field(
		'sphinx_test_connection',
		'Проверка',
		'sphinx_search_test_button',
		'sphinx-search',
		'sphinx_search_test'
	);

	// Настройки отображения.
	add_settings_section(
		'sphinx_search_display',
		'Настройки отображения',
		null,
		'sphinx-search'
	);

	// Настройки подключения
	add_settings_field(
		'sphinx_host',
		'Хост Sphinx',
		'sphinx_search_text_field',
		'sphinx-search',
		'sphinx_search_connection',
		array(
			'field'   => 'sphinx_host',
			'default' => '127.0.0.1',
			'description' => 'Адрес сервера Sphinx. По умолчанию: 127.0.0.1 (локальный сервер)'
		)
	);

	add_settings_field(
		'sphinx_port',
		'Порт Sphinx',
		'sphinx_search_text_field',
		'sphinx-search',
		'sphinx_search_connection',
		array(
			'field'   => 'sphinx_port',
			'default' => '9306',
			'description' => 'Порт для подключения к Sphinx. По умолчанию: 9306 (стандартный порт MySQL-протокола Sphinx)'
		)
	);

	add_settings_field(
		'sphinx_index',
		'Индекс Sphinx',
		'sphinx_search_text_field',
		'sphinx-search',
		'sphinx_search_connection',
		array(
			'field'   => 'sphinx_index',
			'default' => '',
			'description' => 'Название индекса Sphinx для поиска. Название вашего индекса из настроек сервера Sphinx'
		)
	);

	// Кнопка сохранения в конце секции подключения
	add_settings_field(
		'sphinx_connection_save',
		'',
		'sphinx_search_save_connection',
		'sphinx-search',
		'sphinx_search_connection'
	);
    
    // Разделитель после настроек подключения
	add_settings_field(
		'sphinx_connection_separator',
		'',
		'sphinx_search_section_separator',
		'sphinx-search',
		'sphinx_search_connection'
	);

   	// Разделитель после проверки подключения
	add_settings_field(
		'sphinx_test_separator',
		'',
		'sphinx_search_section_separator',
		'sphinx-search',
		'sphinx_search_test'
	);


	// Настройки отображения
	add_settings_field(
		'show_in_menu',
		'Показывать пункт в главном меню',
		'sphinx_search_checkbox_field',
		'sphinx-search',
		'sphinx_search_display',
		array(
			'field'   => 'show_in_menu',
			'default' => '1',
			'description' => 'Добавляет пункт поиска в главное меню WordPress'
		)
	);

	add_settings_field(
		'menu_position',
		'Позиция в главном меню',
		'sphinx_search_text_field',
		'sphinx-search',
		'sphinx_search_display',
		array(
			'field'   => 'menu_position',
			'default' => '2',
			'description' => 'Позиция пункта меню (1-999). Чем меньше число, тем выше будет пункт'
		)
	);

	add_settings_field(
		'show_in_admin_bar',
		'Показывать в верхней панели',
		'sphinx_search_checkbox_field',
		'sphinx-search',
		'sphinx_search_display',
		array(
			'field'   => 'show_in_admin_bar',
			'default' => '1',
			'description' => 'Добавляет пункт поиска в верхнюю панель WordPress'
		)
	);

	add_settings_field(
		'admin_bar_position',
		'Позиция в верхней панели',
		'sphinx_search_text_field',
		'sphinx-search',
		'sphinx_search_display',
		array(
			'field'   => 'admin_bar_position',
			'default' => '500',
			'description' => 'Позиция пункта в верхней панели (1-999). Чем меньше число, тем левее будет пункт'
		)
	);
    
	add_settings_field(
        'show_in_settings',
		'Показывать в меню настроек',
		'sphinx_search_checkbox_field',
		'sphinx-search',
		'sphinx_search_display',
		array(
            'field'   => 'show_in_settings',
			'default' => '1',
			'description' => 'Добавляет пункт настроек плагина в меню настроек WordPress'
            )
    );

    // Кнопка сохранения в конце секции отображения
    add_settings_field(
        'sphinx_display_save',
        '',
        'sphinx_search_save_connection',
        'sphinx-search',
        'sphinx_search_display'
    );
}

add_action( 'admin_init', 'sphinx_search_settings_init' );

// Функции отображения полей настроек
function sphinx_search_text_field($args) {
    $options = get_option('sphinx_search_options', []);
    $value = isset($options[$args['field']]) ? $options[$args['field']] : $args['default'];
    $description = isset($args['description']) ? $args['description'] : '';
    
    echo "<input type='text' name='sphinx_search_options[{$args['field']}]' value='" . esc_attr($value) . "' class='regular-text'>";
    if ($description) {
        echo "<p class='description'>" . esc_html($description) . "</p>";
    }
}

function sphinx_search_checkbox_field($args) {
    $options = get_option('sphinx_search_options', []);
    $field = $args['field'];
    $value = isset($options[$field]) ? $options[$field] : $args['default'];
    $description = isset($args['description']) ? $args['description'] : '';
    
    echo "<label>";
    echo "<input type='hidden' name='sphinx_search_options[{$field}]' value='0'>";
    echo "<input type='checkbox' name='sphinx_search_options[{$field}]' value='1'" . checked('1', $value, false) . ">";
    if ($description) {
        echo "<span class='description'>" . esc_html($description) . "</span>";
    }
    echo "</label>";
}

// Страница настроек
// Проверка подключения к Sphinx
function sphinx_search_test_connection() {
    check_ajax_referer('sphinx_test_connection', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Недостаточно прав');
    }

    $options = get_option('sphinx_search_options');
    $host = isset($options['sphinx_host']) ? $options['sphinx_host'] : '127.0.0.1';
    $port = isset($options['sphinx_port']) ? (int)$options['sphinx_port'] : 9306;
    $index = isset($options['sphinx_index']) ? $options['sphinx_index'] : '';

    try {
        $sphinx = new mysqli($host, '', '', '', $port);
        
        if ($sphinx->connect_error) {
            throw new Exception('Ошибка подключения: ' . $sphinx->connect_error);
        }

        // Проверяем индекс, если он указан
        if (!empty($index)) {
            $result = $sphinx->query("SHOW TABLES LIKE '{$index}'");
            if (!$result || $result->num_rows === 0) {
                throw new Exception('Индекс "' . esc_html($index) . '" не найден');
            }
        }

        $sphinx->close();
        wp_send_json_success('Подключение успешно установлено');
        
    } catch (Exception $e) {
        wp_send_json_error($e->getMessage());
    }
}
add_action('wp_ajax_sphinx_test_connection', 'sphinx_search_test_connection');

// Кнопка сохранения для секции подключения
function sphinx_search_save_button() {
    submit_button('Сохранить изменения', 'primary', 'submit', false);
}

function sphinx_search_test_button() {
    $nonce = wp_create_nonce('sphinx_test_connection');
    $ajax_url = admin_url('admin-ajax.php');
    ?>
    <div class="sphinx-test-connection">
        <button 
        type="button" 
        id="sphinx-test-connection" 
        class="button button-secondary" 
        onclick="
                var button = this;
                var result = document.getElementById('sphinx-connection-result');
                button.disabled = true;
                result.innerHTML = '<span style=\'color: #666;\'>Проверка подключения...</span>';
                
                fetch('<?php echo $ajax_url; ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=sphinx_test_connection&nonce=<?php echo $nonce; ?>'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        result.innerHTML = '<span style=\'color: green\'>' + data.data + '</span>';
                    } else {
                        result.innerHTML = '<span style=\'color: red\'>' + data.data + '</span>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                result.innerHTML = '<span style=\'color: red\'>Ошибка при выполнении запроса</span>';
                })
                .finally(() => {
                    button.disabled = false;
                });
                "
        >Проверить подключение</button>
        <p class="description">Нажмите кнопку, чтобы проверить подключение к серверу Sphinx</p>
        <div id="sphinx-connection-result" style="margin-top: 5px;"></div>
    </div>
    <?php
}

function sphinx_search_options_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <?php settings_errors('sphinx_search'); ?>
        <form action="options.php" method="post">
            <?php
            settings_fields('sphinx_search');
            do_settings_sections('sphinx-search');
            ?>
        </form>
    </div>
    <?php
}

// Функция поиска
function sphinx_search_page() {
    $options = get_option('sphinx_search_options', [
        'sphinx_host' => '127.0.0.1',
        'sphinx_port' => '9306',
        'sphinx_index' => '',
        'show_in_menu' => '1',
        'show_in_settings' => '1',
        'show_in_admin_bar' => '1',
        'menu_position' => '2',
        'admin_bar_position' => '500'
    ]);

    // Форма поиска
    $output = '<style>
        .sphinx-search-form {
            margin: 20px 0;
            padding: 20px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        .sphinx-search-input-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .sphinx-search-form input[type="text"] {
            flex: 1;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            margin: 0;
        }
        .sphinx-search-form input[type="text"]:focus {
            border-color: #2271b1;
            outline: none;
        }
        .sphinx-search-form .button-primary {
            padding: 12px 25px;
            height: auto;
            margin: 0;
        }
    </style>';
    $output .= '<div class="sphinx-search-container">';
    $output .= '<form method="get" class="sphinx-search-form">
        <input type="hidden" name="page" value="sphinx-search">
        <div class="sphinx-search-input-group">
            <input type="text" name="search" value="' . esc_attr(isset($_GET['search']) ? $_GET['search'] : '') . '" placeholder="Поиск...">
            <input type="submit" value="Найти" class="button button-primary">
        </div>
    </form>';

    $search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

    if ($search_query) {
        // Подключение к Sphinx
        try {
            $sphinx = new mysqli($options['sphinx_host'], '', '', '', (int)$options['sphinx_port']);
            
            if ($sphinx->connect_error) {
                throw new Exception('Ошибка подключения к Sphinx: ' . $sphinx->connect_error);
            }

            // Запрос к Sphinx
            $escaped_query = $sphinx->real_escape_string($search_query);
            $sphinx_results = $sphinx->query(
                "SELECT id FROM {$options['sphinx_index']} WHERE MATCH('{$escaped_query}') LIMIT 500 OPTION max_matches=1000;"
            );

            if ($sphinx_results && $sphinx_results->num_rows > 0) {
                $post_ids = [];
                while ($row = $sphinx_results->fetch_assoc()) {
                    $post_ids[] = $row['id'];
                }

                // Получение данных из WordPress
                if (!empty($post_ids)) {
                    $args = array(
                        'post__in' => $post_ids,
                        'orderby' => 'date',
                        'order' => 'DESC',
                        'post_type' => 'any',
                        'posts_per_page' => -1
                    );
                    $query = new WP_Query($args);
                    
                    if ($query->have_posts()) {
                        $output .= '<div class="sphinx-search-results">';
                        $output .= '<h2>' . $query->found_posts . ' результатов поиска по запросу: ' . esc_html($search_query) . '</h2>';
                        while ($query->have_posts()) {
                            $query->the_post();
                            $output .= '<div class="sphinx-search-result">';
                            $output .= '<h3><time>' . get_the_date('d.m.Y') . '</time> <a href="' . get_permalink() . '">' . get_the_title() . '</a></h3>';
                            $output .= '<div class="excerpt">' . get_the_excerpt() . '</div>';
                            $output .= '<div class="meta">Тип: ' . get_post_type_object(get_post_type())->labels->singular_name . '</div>';
                            $output .= '</div>';
                        }
                        $output .= '</div>';
                        wp_reset_postdata();
                    } else {
                        $output .= '<p class="sphinx-no-results">По вашему запросу ничего не найдено</p>';
                    }
                }
            } else {
                $output .= '<p class="sphinx-no-results">По вашему запросу ничего не найдено</p>';
            }

            $sphinx->close();

        } catch (Exception $e) {
            error_log('Sphinx search error: ' . $e->getMessage());
            $output .= '<p class="sphinx-error">Ошибка при выполнении поиска: ' . esc_html($e->getMessage()) . '</p>';
        }
    }

    // Добавляем инструкцию
    $output .= '<div class="sphinx-search-help">';
    $output .= '<h3>Краткая инструкция</h3>
    <p>- Поиск работает в заголовках (title), отрывках (excerpt) и тексте (content), только в опубликованных материалах;</p>
    <p>- Обновление поиска (индексация) происходит по расписанию, каждые 25 минут;</p>
    <p>- Использование слов через пробел «большой город», эквивалентно поиску двух слов «большой+город», то есть будут найдены результаты содержащие, и «большой», и «город»;</p>
    <p>- По-умолчанию, включена полная лемматизация русских слов. Поиск выделит лемму из введённого вами слова и найдёт все совпадения, но бывают тяжёлые случаи, такие как «абырвалг» или «главтрансдепстрой», которых нет в словаре;</p>
    <p>- Если нужна вариативность (в тяжёлых случаях), используйте символ «или»: «|», например: «главрыба | главрыбу | главрыбы» соберёт все три комбинации слова в результате поиска;</p>
    <p>- Для точного поиска используйте английские кавычки;</p>
    <p>- Можно комбинировать точный поиск (в кавычках), дополнительные слова и символ «|»;</p>
    <p>- Вывод результатов ограничен 500 ссылками.</p>';
    $output .= '</div>';
    $output .= '</div>';

    return $output;
}

/**
 * Функция обратного вызова для проверки подключения к Sphinx
 */
function sphinx_search_connection_callback() {
    $options = get_option('sphinx_search_options');
    $host = isset($options['sphinx_host']) ? $options['sphinx_host'] : '127.0.0.1';
    $port = isset($options['sphinx_port']) ? $options['sphinx_port'] : '9306';
    
    try {
        $conn = mysqli_connect($host, '', '', '', $port);
        if ($conn) {
            mysqli_close($conn);
            return true;
        }
    } catch (Exception $e) {
        return false;
    }
    return false;
}

// Регистрируем шорткод
add_shortcode('sphinx_search', 'sphinx_search_page');

// Добавляем ссылку на настройки на странице плагинов
function sphinx_search_plugin_action_links($links) {
    $settings_link = '<a href="' . admin_url('options-general.php?page=sphinx-search-settings') . '">Настройки</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'sphinx_search_plugin_action_links');
