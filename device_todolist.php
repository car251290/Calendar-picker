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
        <label id="toggle-device-label" class="toggle-label">
            Select Devices
        </label>
        <div id="dropdown-container" class="dropdown-container">
            <ul id="dropdown">
                <?php
                $db = Factory::getDbo();
                $query = "SELECT CONCAT(`name`, ' [', `serial_number`, ']') AS val, `serial_number` as name FROM `#__iot_devices` ORDER BY val ASC";
                $db->setQuery($query);
                $devices = $db->loadObjectList();

                if (!empty($devices)) :
                    foreach ($devices as $device) : ?>
                        <li class="checkbox-item">
                            <label for="device-<?php echo htmlspecialchars((string) $device->name, ENT_QUOTES, 'UTF-8'); ?>">
                                <input
                                        type="checkbox"
                                        id="device-<?php echo htmlspecialchars((string) $device->name, ENT_QUOTES, 'UTF-8'); ?>"
                                        name="device[]"
                                        value="<?php echo htmlspecialchars((string) $device->name, ENT_QUOTES, 'UTF-8'); ?>"
                                        data-name="<?php echo htmlspecialchars((string) $device->val, ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars((string) $device->val, ENT_QUOTES, 'UTF-8'); ?>
                            </label>
                            <span class="remove-item" data-device-id="device-<?php echo htmlspecialchars((string) $device->name, ENT_QUOTES, 'UTF-8'); ?>">✖</span>
                        </li>
                    <?php endforeach; ?>
                <?php else : ?>
                    <p>No devices available</p>
                <?php endif; ?>
            </ul>
        </div>
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
<!-- Custom Script to Handle Dropdown, Checkbox Selection, and Collapse -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectElement = document.getElementById('device-select');
        const dropdown = document.getElementById('dropdown');
        const toggleLabel = document.getElementById('toggle-device-label');
        const dropdownContainer = document.getElementById('dropdown-container');

        function toggleDropdown() {
            dropdownContainer.classList.toggle('open');
        }

        function updateLabelText() {
            const selectedCheckboxes = dropdown.querySelectorAll('input[type="checkbox"]:checked');
            const selectedDevices = Array.from(selectedCheckboxes).map(checkbox => checkbox.dataset.name);
            const uniqueDevices = [...new Set(selectedDevices)];

            if (uniqueDevices.length >= 2) {
                toggleLabel.textContent = `Multiple devices selected: ${uniqueDevices[uniqueDevices.length - 1]}`;
            } else if (uniqueDevices.length === 1) {
                toggleLabel.textContent = `Selected device: ${uniqueDevices[0]}`;
            } else {
                toggleLabel.textContent = 'Select Devices';
            }
        }

        toggleLabel.addEventListener('click', function(event) {
            event.preventDefault();
            toggleDropdown();
        });

        dropdown.addEventListener('change', updateLabelText);

        dropdown.addEventListener('click', function(event) {
            if (event.target.classList.contains('remove-item')) {
                const deviceId = event.target.getAttribute('data-device-id');
                const checkbox = document.getElementById(deviceId);
                if (checkbox) {
                    checkbox.checked = false;
                }
                updateLabelText();
            }
        });

        document.addEventListener('click', function(event) {
            if (!dropdownContainer.contains(event.target) && !toggleLabel.contains(event.target)) {
                dropdownContainer.classList.remove('open');
            }
        });
    });
</script>

<style>
    .joomla-form-field-list-fancy-select {
        position: relative;
        width: 30%;
    }

    .joomla-form-field-list-fancy-select ul {
        list-style: none;
        padding: 0;
        margin: 0;
        border: 1px solid #c80e0e;
        max-height: 200px;
        overflow-y: auto;
    }

    .joomla-form-field-list-fancy-select .checkbox-item {
        padding: 5px;
        border-bottom: 1px solid #ffffff;
    }

    .joomla-form-field-list-fancy-select .remove-device {
        position: absolute;
        top: 5px;
        right: 10px;
        cursor: pointer;
        color: red;
        font-size: 18px;
    }

    .dropdown-container.open ul {
        display: block;
    }

    .toggle-label {
        background-color: rgb(170, 177, 184);
        color: #ffffff;
        padding: 10px;
        cursor: pointer;
        text-align: center;
        width: auto;
        max-width: 100%;
        border-radius: 8px;
        display: block;
        margin: 0 auto;
    }

    .dropdown-container {
        position: relative;
    }

    .dropdown-container ul {
        display: none;
        margin: 0;
        padding: 0;
        list-style: none;
        border: 1px solid #c80e0e;
        background-color: #ffffff;
        max-height: 200px;
        overflow-y: auto;
        width: 100%;
        position: relative;
        padding-right: 40px; /* Space for the "X" button */
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        border-radius: 8px;
    }

    .dropdown-container.open ul {
        display: block;
    }

    .checkbox-item {
        padding: 5px;
        border-bottom: 1px solid #f0f0f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .remove-item {
        cursor: pointer;
        color: red;
        font-size: 14px;
    }
</style>



