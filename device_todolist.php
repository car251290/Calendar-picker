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
$deviceCount = count($selectedDevices); // Calculate the number of selected devices

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = Factory::getApplication()->getDocument()->getWebAssetManager();

?>

<?php if ($filters) : ?>
    <div id="device-count" style="margin-bottom: 10px; font-weight: bold;">
        <?php echo $deviceCount > 0
            ? "$deviceCount device" . ($deviceCount > 1 ? "s have" : " has") . " been selected."
            : "No device has been selected."; ?>
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


<form id="device-selection-form" class="container mt-4">
    <div class="device-selection-container mb-3">
        <label for="device-select-copy" class="form-label fw-bold">Select Devices</label>
        <select id="device-select-copy" name="device" class="form-select" multiple aria-label="Select devices">
            <option value="device1">Device 1</option>
            <option value="device2">Device 2</option>
            <option value="device3">Device 3</option>
        </select>
    </div>

    <div id="selected-devices-copy" class="d-flex flex-wrap gap-2 mt-3 p-2 border border-secondary rounded" aria-live="polite">
        <!-- Selected devices will appear here -->
    </div>

    <div id="device-count-copy" class="mt-2 fw-bold">
        <!-- Count of selected devices -->
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const selectedDevicesCopy = [];
        const deviceSelectCopy = document.getElementById('device-select-copy');
        const selectedDevicesContainerCopy = document.getElementById('selected-devices-copy');
        const deviceCountElementCopy = document.getElementById('device-count-copy');

        if (!deviceSelectCopy || !selectedDevicesContainerCopy || !deviceCountElementCopy) {
            console.error('Required elements for device selection UI are missing.');
            return;
        }

        function updateDeviceUICopy() {
            selectedDevicesContainerCopy.innerHTML = '';
            selectedDevicesCopy.forEach(device => {
                const deviceBox = document.createElement('div');
                deviceBox.className = 'device-box border border-primary';
                deviceBox.textContent = device;
                deviceBox.addEventListener('click', () => removeDeviceCopy(device));
                selectedDevicesContainerCopy.appendChild(deviceBox);
            });
            const count = selectedDevicesCopy.length;
            deviceCountElementCopy.textContent = `${count} device${count === 1 ? '' : 's'} selected.`;
        }

        function addDeviceCopy(deviceName) {
            if (!selectedDevicesCopy.includes(deviceName)) {
                selectedDevicesCopy.push(deviceName);
                updateDeviceUICopy();
            }
        }

        function removeDeviceCopy(deviceName) {
            const index = selectedDevicesCopy.indexOf(deviceName);
            if (index !== -1) {
                selectedDevicesCopy.splice(index, 1);
                Array.from(deviceSelectCopy.options).forEach(option => {
                    if (option.value === deviceName) {
                        option.selected = false;
                    }
                });
                updateDeviceUICopy();
            }
        }

        function syncSelectedDevicesCopy() {
            selectedDevicesCopy.length = 0;
            Array.from(deviceSelectCopy.selectedOptions).forEach(option => {
                selectedDevicesCopy.push(option.value);
            });
            updateDeviceUICopy();
        }

        deviceSelectCopy.addEventListener('change', syncSelectedDevicesCopy);
        syncSelectedDevicesCopy();
    });
</script>

<style>
    .device-selection-container {
        margin: 20px 0;
    }

    .device-selection-container label {
        font-size: 1.2em;
        font-weight: bold;
        display: block;
        margin-bottom: 10px;
    }
    .device-box {
        background-color: #f0f0f0;
        width: 100px; /* Fixed width for a square */
        height: 100px; /* Fixed height for a square */
        padding: 12px;
        border-radius: 5px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 1em;
        transition: background-color 0.3s ease-in-out, transform 0.2s;
        text-align: center;
    }

    .device-box:hover {
        background-color: #007bff;
        color: #fff;
        transform: scale(1.05);
    }

    #device-select-copy {
        width: 100%;
        padding: 10px;
        font-size: 1em;
        border: 1px solid #ccc;
        border-radius: 0.25rem; /* Bootstrap-style rounded corners */
        background-color: #fff;
        transition: border-color 0.3s ease-in-out;
    }

    #device-select-copy:focus {
        border-color: #007bff;
        outline: none;
    }

    #selected-devices-copy {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 15px;
    }

    .device-box {
        background-color: #f0f0f0;
        padding: 8px 12px;
        border-radius: 5px;
        display: inline-block;
        cursor: pointer;
        font-size: 1em;
        transition: background-color 0.3s ease-in-out;
    }

    .device-box:hover {
        background-color: #007bff;
        color: #fff;
    }

    #device-count-copy {
        font-size: 1.1em;
        margin-top: 15px;
        font-weight: bold;
    }
</style>
