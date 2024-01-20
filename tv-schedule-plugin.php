<?php
/*
Plugin Name: TV Schedule Plugin
Description: Display a TV schedule 
Version: 1.0
Author: Microweb Global (PVT) LTD
*/

require_once(plugin_dir_path(__FILE__) . 'vendor/autoload.php');

use GuzzleHttp\Client;

date_default_timezone_set('Asia/Colombo');

function tv_schedule_settings_page()
{
?>
    <div class="wrap">
        <h1>TV Schedule Plugin Settings</h1>
        <h2>Developed by Microweb Global (PVT) LTD</h2>
        <h3>Digital Empowerment at its Finest</h3>
        <h3>www.microweb.global</h3>
        <h3>contact@microweb.global</h3>
        <img src="https://media.licdn.com/dms/image/D560BAQFiTEsFjMiy4g/company-logo_200_200/0/1682276360757?e=2147483647&v=beta&t=btygzcEDfXABJnHW8hx7DNbzwDE46BefRsjdIsYFkk8" alt="Developer's Logo" width="200" height="auto" />
        <form method="post" action="options.php">
            <?php
            settings_fields('tv_schedule_settings_group');
            do_settings_sections('tv_schedule_settings_page');
            submit_button();
            ?>
        </form>
    </div>
<?php
}

function tv_schedule_register_settings()
{
    register_setting('tv_schedule_settings_group', 'tv_schedule_sheet_url');
    add_settings_section('tv_schedule_settings_section', 'Sheet URL', '', 'tv_schedule_settings_page');
    add_settings_field('tv_schedule_sheet_url', 'Enter your sheet URL:', 'tv_schedule_sheet_url_callback', 'tv_schedule_settings_page', 'tv_schedule_settings_section');
}

add_action('admin_init', 'tv_schedule_register_settings');

function tv_schedule_sheet_url_callback()
{
    $sheet_url = get_option('tv_schedule_sheet_url');
    echo "<input type='text' name='tv_schedule_sheet_url' value='$sheet_url' size='50' />";
}

function tv_schedule_menu()
{
    add_menu_page('TV Schedule', 'TV Schedule', 'manage_options', 'tv_schedule_settings', 'tv_schedule_settings_page');
}

add_action('admin_menu', 'tv_schedule_menu');

function tv_schedule_shortcode($atts)
{
    ob_start();
?>
    <style>
        .schedule-days {
            display: flex;
            justify-content: space-between;
            border: 1px solid #ccc;
            padding: 5px;
            margin-bottom: 10px;
        }

        .selected-date,
        .current-time {
            display: inline-block;
            padding-top: 10px;
            margin-right: 10px;
        }

        .schedule-days {
            margin-bottom: 20px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th {
            background-color: #f2f2f2;
            color: black;
            padding: 8px;
            font-family: 'Poppins', sans-serif;
            font-weight: bold;
        }

        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            font-family: 'Poppins', sans-serif;
            font-weight: bold;
        }

        th {
            background-color: #f2f2f2;
        }

        body {
            font-family: 'Poppins', sans-serif;
        }



        @media (max-width: 768px) {
            .schedule-table-container {
                overflow-x: auto;
            }
        }

        @media (max-width: 768px) {
            .schedule-days {
                overflow-x: auto;
                white-space: nowrap;
                margin-bottom: 10px;
            }

            .day-selector {
                margin-right: 2px;
            }
        }
    </style>
    <div class="tv-schedule">
        <?php
        $sheet_url = get_option('tv_schedule_sheet_url', 'https://docs.google.com/spreadsheets/d/14Q4wM82bWXDKrSa7ykerU71Ynq20UmCGDnYhr_WYVeA/export?format=csv');

        $client = new Client();
        $response = $client->get($sheet_url);
        $data = array_map('str_getcsv', explode("\n", $response->getBody()));

        if (!empty($data)) {
            echo '<div class="schedule-days">';
            foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day) {
                echo '<div class="day-selector" data-day="' . strtolower($day) . '" onclick="toggleSchedule(\'' . strtolower($day) . '\')">' . $day . '</div>';
            }
            echo '</div>';
            echo '<div class="date-time-container">';
            echo '<div id="selectedDate" class="selected-date"></div>';
            echo '<div id="currentTime" class="current-time"></div>';
            echo '</div>';
            echo '<table class="table" id="scheduleTable">';
            echo '<thead>';
            echo '<tr>';
            echo '<th>Time</th>';
            echo '<th>Program</th>';
            echo '<th>Description</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';

            foreach ($data as $row) {
                $dateParts = explode('/', $row[0]);

                if (count($dateParts) === 3) {
                    echo '<tr data-day="' . strtolower(date('l', strtotime("{$dateParts[2]}-{$dateParts[1]}-{$dateParts[0]}"))) . '">';
                    echo '<td>' . $row[1] . '</td>';
                    echo '<td>' . $row[2] . '</td>';
                    echo '<td>' . $row[3] . '</td>';
                    echo '</tr>';
                }
            }

            echo '</tbody>';
            echo '</table>';
        } else {
            echo '<p>No data available.</p>';
        }
        ?>
    </div>

    <script>
        var data = <?php echo json_encode($data); ?>;

        function getSelectedDate(day) {
            if (!day) {
                console.error('Error: Day is undefined or null');
                return '';
            }

            var selectedRow = null;
            for (var i = 0; i < data.length; i++) {
                var dateParts = data[i][0].split('/');
                if (dateParts.length === 3) {
                    var currentDay = new Date(dateParts[2], dateParts[1] - 1, dateParts[0]).toLocaleDateString('en-US', {
                        weekday: 'long'
                    }).toLowerCase();
                    if (currentDay === day.toLowerCase()) {
                        selectedRow = data[i];
                        break;
                    }
                }
            }

            if (!selectedRow) {
                console.error('Error: No data found for the specified day');
                return ''; 
            }

            var sheetDate = selectedRow[0];
            var dateParts = sheetDate.split('/');
            var day = parseInt(dateParts[0]);
            var month = parseInt(dateParts[1]) - 1; 
            var year = parseInt(dateParts[2]);

            var currentDate = new Date(year, month, day);

            if (isNaN(currentDate.getTime())) {
                console.error('Error: Unable to parse date with the supported format');
                console.log('Encountered date format:', sheetDate);
                return ''; 
            }

            var options = {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            };
            var formattedDate = currentDate.toLocaleDateString('en-US', options);

            console.log('Selected Date for', day, ':', formattedDate);

            return formattedDate;
        }

        function updateSelectedDate(selectedDate) {
            var dateElement = document.getElementById('selectedDate');
            if (dateElement) {
                dateElement.innerHTML = selectedDate;
            }
        }

        function updateCurrentTime() {
            var timeElement = document.getElementById('currentTime');
            if (timeElement) {
                var currentTime = new Date();
                var options = {
                    hour: 'numeric',
                    minute: 'numeric',
                    second: 'numeric'
                };
                var formattedTime = currentTime.toLocaleTimeString('en-US', options);
                timeElement.innerHTML = '' + formattedTime;
            }
        }

        function toggleSchedule(day) {
            var rows = document.querySelectorAll('#scheduleTable tbody tr');

            var selectedDate = getSelectedDate(day);


            rows.forEach(function(row) {
                if (day === row.getAttribute('data-day')) {
                    row.style.display = 'table-row';
                } else {
                    row.style.display = 'none';
                }
            });

            updateSelectedDate(selectedDate);

            var daySelectors = document.querySelectorAll('.day-selector');
            daySelectors.forEach(function(selector) {
                if (day === selector.getAttribute('data-day')) {
                    selector.classList.add('selected-day');
                } else {
                    selector.classList.remove('selected-day');
                }
            });
        }


        function displayCurrentTime() {
            updateCurrentTime();
            setInterval(updateCurrentTime, 1000);
        }

        var currentDay = '<?php echo strtolower(date('l')); ?>';
        toggleSchedule(currentDay);
        displayCurrentTime();
        highlightCurrentTimeRow();




        function highlightCurrentTimeRow() {
            var currentTime = new Date();
            var currentHour = currentTime.getHours();
            var currentMinutes = currentTime.getMinutes();
            var currentTimeInMinutes = currentHour * 60 + currentMinutes;

            var currentDay = '<?php echo strtolower(date('l')); ?>';

            var rows = document.querySelectorAll('#scheduleTable tbody tr');

            var closestRow;
            var closestTimeDiff = Number.POSITIVE_INFINITY;

            rows.forEach(function(row) {
                var rowDay = row.getAttribute('data-day');
                if (currentDay === rowDay) {
                    var rowTime = row.querySelector('td:first-child').innerText.trim();
                    var rowHour = parseInt(rowTime.split(':')[0]);
                    var rowMinutes = parseInt(rowTime.split(':')[1]);

                    var rowTimeInMinutes = rowHour * 60 + rowMinutes;
                    var timeDiff = currentTimeInMinutes - rowTimeInMinutes;

                    if (timeDiff >= 0 && timeDiff < closestTimeDiff) {
                        closestTimeDiff = timeDiff;
                        closestRow = row;
                    }
                }
            });

            rows.forEach(function(row) {
                row.style.color = '';
            });

            if (closestRow) {
                closestRow.style.color = 'red';

                var program = closestRow.querySelector('td:nth-child(2)').innerText.trim();
                console.log('Highlighted Program Text Color for Today:', program);
            }
        }





        highlightCurrentTimeRow();
    </script>



    <style>
        .schedule-days {
            display: flex;
            justify-content: space-between;
            border: 1px solid #ccc;
            padding: 5px;
            margin-bottom: 10px;
        }

        .day-selector.selected-day {
            background-color: #b01c34;
            color: white;
        }

        .day-selector {
            cursor: pointer;
            padding: 5px;
            border: 1px solid #ccc;
        }
    </style>

<?php
    return ob_get_clean();
}

add_shortcode('tv_schedule', 'tv_schedule_shortcode');
?>