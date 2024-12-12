<?php

/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   (C) 2013 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormHelper;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;

$shouldDisplayReceivedTime = str_contains($displayData['customValues']['received-time-display'], 'yes');
// the device if selected and the device count is calculated
$selectedDevices = isset($displayData['selectedDevices']) ? $displayData['selectedDevices'] : [];

if(!function_exists('shouldShowField')) {
    function shouldShowField($name) {
        global $shouldDisplayReceivedTime;

        $isReceivedTimeField = str_contains($name, 'received');
        return (!$isReceivedTimeField || ($isReceivedTimeField && $shouldDisplayReceivedTime));
    }
}

if(!function_exists('styleForField')) {
    function styleForField($name) {
        $isTimeField = str_contains($name, 'time');
        $maxWidth = $isTimeField ? "210" : "600";
        echo "style=\"max-width: {$maxWidth}px; max-height: 48px;\"";
    }
}

if(!function_exists('dataShowFor')) {
    function dataShowFor($field) {
        global $wa; // Ensure $wa is available in this function
        $dataShowOn = '';
        if ($field->showon) {
            $wa->useScript('showon');
            $conditions = json_encode(FormHelper::parseShowOnConditions($field->showon, $field->formControl, $field->group));
            $dataShowOn = "data-showon='{$conditions}'";
        }
        echo $dataShowOn;
    }
}

// Load the form filters
$filters = $displayData['view']->filterForm->getGroup('filter');
$deviceCount = count($selectedDevices);

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = Factory::getApplication()->getDocument()->getWebAssetManager();

?>

<?php if ($filters) : ?>
    <div id="device-count" style="margin-bottom: 10px; font-weight: bold;">
        <?php echo $deviceCount > 0
            ? "$deviceCount device" . ($deviceCount > 1 ? "s have" : " has"): ""; ?>
    </div>
    <?php foreach ($filters as $name => $field) : ?>
        <?php if (shouldShowField($name)) : ?>
            <div <?php styleForField($name); ?> class="js-stools-field-filter" <?php dataShowFor($field); ?>>
                <span class="visually-hidden"><?php echo $field->label; ?></span>
                <?php echo $field->input; ?>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select with Checkboxes on the Right</title>

    <!-- MDBootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/mdbootstrap/css/mdb.min.css" rel="stylesheet">

    <!-- Optional: Custom Style -->
    <style>
        body {
            padding: 50px;
        }

        .custom-select-wrapper {
            position: relative;
            width: 100%;
            max-width: 600px;
        }

        .custom-select {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            cursor: pointer;
        }

        /* Custom dropdown for checkboxes */
        .custom-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            border: 1px solid #ccc;
            background-color: #fff;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            display: none;
            max-height: 250px;
            overflow-y: auto;
            z-index: 100;
        }

        .custom-dropdown.open {
            display: block;
        }

        /* Style for the checkbox list */
        .checkbox-list {
            list-style-type: none;
            margin: 0;
            padding: 0;
        }

        .checkbox-item {
            padding: 10px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .checkbox-item:last-child {
            border-bottom: none;
        }

        .checkbox-item label {
            margin-right: auto;
        }

        .checkbox-item input[type="checkbox"] {
            margin-left: auto;
        }

        /* Clear Selection Button */
        .clear-selection {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #888;
            font-size: 18px;
            padding: 0 5px;
            transition: color 0.3s;
        }

        .clear-selection:hover {
            color: red;
        }

        .clear-selection.hidden {
            display: none;
        }

        /* Style for the "Multiple devices selected" label */
        .multiple-devices {
            font-style: italic;
            color: #888;
        }

        /* Style for the "X" button next to each checkbox */
        .checkbox-item .remove-device {
            font-size: 18px;
            cursor: pointer;
            color: red;
            margin-left: 10px;
        }

        .checkbox-item .remove-device:hover {
            color: darkred;
        }

    </style>
</head>
<body>

<div class="form-group custom-select-wrapper">
    <label for="device-select"></label>
    <div class="custom-select" id="device-select">
        <span id="selected-devices-text">Select a device</span>
        <span id="clear-selection" class="clear-selection hidden">&#10006;</span>
    </div>

    <!-- Custom dropdown with checkboxes -->
    <div id="dropdown" class="custom-dropdown">
        <ul class="checkbox-list">

            <?php
            // Replace with dynamic device options using Joomla's database query
            $db = Factory::getDbo();
            $query = "SELECT CONCAT(`name`, ' [', `serial_number`, ']') AS val, `serial_number` as name FROM `#__iot_devices` ORDER BY val ASC";
            $db->setQuery($query);
            $devices = $db->loadObjectList();

            // Loop through the fetched devices and generate the <li> items with checkboxes
            foreach ($devices as $device) : ?>
                <li class="checkbox-item">
                    <label>
                        <input type="checkbox" value="<?php echo $device->name; ?>" data-name="<?php echo $device->val; ?>">
                        <?php echo $device->val; ?>
                    </label>
                    <span class="remove-device" data-name="<?php echo $device->name; ?>">&#10006;</span>
                </li>
            <?php endforeach; ?>

        </ul>
    </div>
</div>

<!-- MDBootstrap JS -->
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/mdbootstrap/js/mdb.min.js"></script>

<!-- Custom Script to Handle Dropdown, Checkbox, and Clear Selection -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectElement = document.getElementById('device-select');
        const dropdown = document.getElementById('dropdown');
        const selectedText = document.getElementById('selected-devices-text');
        const clearSelectionButton = document.getElementById('clear-selection');

        // Function to toggle the dropdown visibility
        function toggleDropdown() {
            dropdown.classList.toggle('open');
        }

        // Function to handle checkbox selection and update the text
        function updateSelectedDevices() {
            const selectedCheckboxes = Array.from(dropdown.querySelectorAll('input[type="checkbox"]:checked'));
            const selectedValues = selectedCheckboxes.map(checkbox => checkbox.dataset.name);

            if (selectedValues.length > 4) {
                // If more than 4 devices are selected, show "Multiple devices selected"
                const lastDevice = selectedValues.pop(); // Remove the last device from the list
                selectedText.textContent = `Multiple devices selected, last device: ${lastDevice}`;
                selectedText.classList.add('multiple-devices');
            } else if (selectedValues.length > 0) {
                // Otherwise, show the selected devices as a comma-separated list
                selectedText.textContent = selectedValues.join(', ');
                selectedText.classList.remove('multiple-devices');
            } else {
                selectedText.textContent = 'Select a device';
                selectedText.classList.remove('multiple-devices');
            }

            // Show/hide the clear selection button
            if (selectedValues.length > 0) {
                clearSelectionButton.classList.remove('hidden');
            } else {
                clearSelectionButton.classList.add('hidden');
            }
        }

        // Toggle dropdown when select is clicked
        selectElement.addEventListener('click', function(event) {
            event.stopPropagation();
            toggleDropdown();
        });

        // Update selected devices when checkbox is clicked
        dropdown.addEventListener('change', updateSelectedDevices);

        // Clear selection when the "X" button is clicked
        clearSelectionButton.addEventListener('click', function() {
            const checkboxes = dropdown.querySelectorAll('input[type="checkbox"]');
            checkboxes.forEach(checkbox => checkbox.checked = false);
            updateSelectedDevices();
            toggleDropdown(); // Close the dropdown
        });

        // Remove device from the selected list when "X" next to the checkbox is clicked
        dropdown.addEventListener('click', function(event) {
            if (event.target.classList.contains('remove-device')) {
                const checkbox = event.target.previousElementSibling.querySelector('input[type="checkbox"]');
                checkbox.checked = false;
                updateSelectedDevices();
            }
        });

        // Close dropdown when clicking outside of it
        document.addEventListener('click', function(event) {
            if (!selectElement.contains(event.target)) {
                dropdown.classList.remove('open');
            }
        });
    });
</script>

</body>
</html>
