<?php

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormHelper;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;

$shouldDisplayReceivedTime = str_contains($displayData['customValues']['received-time-display'], 'yes');
// the device if selected and the device count is calculated
$selectedDevices = isset($displayData['selectedDevices']) ? $displayData['selectedDevices'] : [];

if (!function_exists('shouldShowField')) {
    function shouldShowField($name) {
        global $shouldDisplayReceivedTime;

        $isReceivedTimeField = str_contains($name, 'received');
        return (!$isReceivedTimeField || ($isReceivedTimeField && $shouldDisplayReceivedTime));
    }
}

if (!function_exists('styleForField')) {
    function styleForField($name) {
        $isTimeField = str_contains($name, 'time');
        $maxWidth = $isTimeField ? "210" : "600";
        echo "style=\"max-width: {$maxWidth}px; max-height: 48px;\"";
    }
}

if (!function_exists('dataShowFor')) {
    function dataShowFor($field, $wa) {
        $dataShowOn = '';
        if ($field->showon) {
            $wa->useScript('showon');
            $conditions = json_encode(FormHelper::parseShowOnConditions($field->showon, $field->formControl, $field->group));
            $dataShowOn = "data-showon='{$conditions}'";
        }
        echo $dataShowOn;
    }
}

// Load the form filters changes by JC

$filters = $displayData['view']->filterForm->getGroup('filter');
$deviceCount = count($selectedDevices);

$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
$wa->useScript('showon');

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = Factory::getApplication()->getDocument()->getWebAssetManager();

?>
<?php if ($filters) : ?>
    <div id="device-count" style="margin-bottom: 10px; font-weight: bold;">
        <?php echo $deviceCount > 0 ? "$deviceCount device" . ($deviceCount > 1 ? "s have" : " has") . " been selected." : ''; ?>
    </div>
    <?php if ($deviceCount >= 4) : ?>
        <div style="color: red; font-weight: bold;">
            Multiple devices selected.
        </div>
    <?php endif; ?>
    <div class="joomla-form-field-list-fancy-select" id="device-select">
        <!-- <label for="device-select">Select Devices</label> -->
        <div class="custom-select">
             <span id="selected-devices-text">Select a device</span>
            <span id="clear-selection" class="clear-selection hidden">&#10006;</span>
        </div>
        <ul id="dropdown">
            <?php
            // Fetch dynamic device options using Joomla's database query
            $db = Factory::getDbo();
            $query = "SELECT CONCAT(`name`, ' [', `serial_number`, ']') AS val, `serial_number` as name FROM `#__iot_devices` ORDER BY val ASC";
            $db->setQuery($query);
            $devices = $db->loadObjectList();

            // Loop through the fetched devices and generate the <li> items with checkboxes
            if (!empty($devices)) :
                foreach ($devices as $device) : ?>
                    <li class="checkbox-item">
                        <label for="device-<?php echo htmlspecialchars((string) $device->name, ENT_QUOTES, 'UTF-8'); ?>">
                            <input
                                    type="checkbox"
                                    id="device-<?php echo htmlspecialchars((string) $device->name, ENT_QUOTES, 'UTF-8'); ?>"
                                    value="<?php echo htmlspecialchars((string) $device->name, ENT_QUOTES, 'UTF-8'); ?>"
                                    data-name="<?php echo htmlspecialchars((string) $device->val, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars((string) $device->val, ENT_QUOTES, 'UTF-8'); ?>
                        </label>
                        <span
                                class="remove-device"
                                data-name="<?php echo htmlspecialchars((string) $device->name, ENT_QUOTES, 'UTF-8'); ?>">
                            &#10006;
                        </span>
                    </li>
                <?php endforeach;
            else : ?>
                <p><?php echo 'No devices available'; ?></p>
            <?php endif; ?>
        </ul>
    </div>
    <?php foreach ($filters as $name => $field) : ?>
        <?php if (isset($field) && shouldShowField($name)) : ?>
            <div <?php styleForField($name); ?> class="js-stools-field-filter" <?php dataShowFor($field, $wa); ?>>
                <span class="visually-hidden"><?php echo htmlspecialchars((string) $field->label, ENT_QUOTES, 'UTF-8'); ?></span>
                <?php echo $field->input; ?>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>

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
<style>
    .joomla-form-field-list-fancy-select {
        position: relative;
        display: inline-block;
        width: 100%;
    }

    .joomla-form-field-list-fancy-select label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }

    .joomla-form-field-list-fancy-select ul {
        list-style: none;
        padding: 0;
        margin: 0;
        border: 1px solid #ccc;
        max-height: 200px;
        overflow-y: auto;
    }

    .joomla-form-field-list-fancy-select .checkbox-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 5px;
        border-bottom: 1px solid #eee;
    }

    .joomla-form-field-list-fancy-select .checkbox-item label {
        flex-grow: 1;
    }

    .joomla-form-field-list-fancy-select .remove-device {
        cursor: pointer;
        color: red;
        margin-left: 10px;
    }

    .joomla-form-field-list-fancy-select .multiple-devices {
        color: red;
        font-weight: bold;
    }
</style>
