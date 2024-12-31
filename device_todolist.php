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
$wa->registerAndUseStyle('bootstrap-icons', 'https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css');

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
            -Select Devices-
        </label>
        <div id="dropdown-container" class="dropdown-container" style="display: none;">
            <div class="search-bar-container">
                <input type="text" id="search-bar" placeholder="Search devices..." onkeyup="filterDevices()">
                <i class="bi bi-search search-icon"></i> <!-- Search Icon -->
            </div>
            <ul id="dropdown">
                <?php
                $db = Factory::getDbo();
                $query = "SELECT CONCAT(`name`, ' [', `serial_number`, ']') AS val, `serial_number` as name FROM `#__iot_devices` ORDER BY val ASC";
                $db->setQuery($query);
                $devices = $db->loadObjectList();
                $selectedDevices = []; // Initialize the selected devices array.

                if (!empty($devices)) :
                    foreach ($devices as $device) :
                        // Check if the device is selected
                        $isChecked = in_array($device->name, $selectedDevices) ? 'checked' : ''; ?>
                        <li class="checkbox-item">
                            <label for="device-<?php echo htmlspecialchars((string) $device->name, ENT_QUOTES, 'UTF-8'); ?>">
                                <input
                                        type="checkbox"
                                        id="device-<?php echo htmlspecialchars((string) $device->name, ENT_QUOTES, 'UTF-8'); ?>"
                                        name="device[]"
                                        value="<?php echo htmlspecialchars((string) $device->name, ENT_QUOTES, 'UTF-8'); ?>"
                                        data-name="<?php echo htmlspecialchars((string) $device->val, ENT_QUOTES, 'UTF-8'); ?>"
                                    <?php echo $isChecked; ?>
                                >
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
        const searchBar = document.getElementById('search-bar'); // Corrected variable name

        let lastSelectedDevice = ''; // To track the last selected device

        function toggleDropdown() {
            dropdownContainer.classList.toggle('open');
            searchBar.style.display = searchBar.style.display === 'block' ? 'none' : 'block';
            console.log("Toggled dropdown visibility.");
        }

        toggleLabel.addEventListener('click', function(event) {
            const isVisible = dropdownContainer.style.display === 'block';
            dropdownContainer.style.display = isVisible ? 'none' : 'block';
            event.preventDefault();
            searchBar.classList.toggle('visible');
            toggleDropdown();
        });

        // Function for the search
        function filterDevices() {
            const searchInput = searchBar.value.toLowerCase();
            const items = document.querySelectorAll('#dropdown .checkbox-item');

            items.forEach(item => {
                const text = item.textContent.toLowerCase();
                if (text.includes(searchInput)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        function updateLabelText() {
            const selectedCheckboxes = dropdown.querySelectorAll('input[type="checkbox"]:checked');
            const selectedDevices = Array.from(selectedCheckboxes).map(checkbox => checkbox.dataset.name);
            const uniqueDevices = [...new Set(selectedDevices)];
            const selectedCount = uniqueDevices.length;

            let labelText = '';
            if (selectedCount >= 4) {
                // If 4 or more devices are selected, show only the last selected device
                labelText = `Multiple devices selected. Last selected: ${lastSelectedDevice}`;
            } else if (selectedCount === 3) {
                labelText = `3 devices selected: ${selectedDevices.join(', ')}`;
            } else if (selectedCount === 2) {
                labelText = `2 devices selected: ${selectedDevices.join(', ')}`;
            } else if (selectedCount === 1) {
                labelText = `1 device selected: ${selectedDevices[0]}`;
            } else {
                labelText = 'Select Devices';
            }

            toggleLabel.textContent = labelText;
        }

        // Event listener to update the last selected device on checkbox change
        dropdown.addEventListener('change', function(event) {
            if (event.target.type === 'checkbox') {
                // If the checkbox is checked, update the last selected device
                if (event.target.checked) {
                    lastSelectedDevice = event.target.dataset.name;
                }
                updateLabelText();
            }
        });

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
                dropdownContainer.style.display = 'none';
                searchBar.classList.remove('visible');
            }
        });
    });
</script>

<style>

    .dropdown-container.open ul {
        display: block;
    }

    .toggle-label {
        background-color: rgba(170, 177, 184, 0.5); /* Gray-white with transparency */
        color: black;
        padding: 10px;
        cursor: pointer;
        text-align: center;
        width: auto;
        height: auto;
        max-width: 100%;
        border-radius: 12px;
        display: block;
        margin: 0 auto;
    }

    #search-bar {
        display: block;
        width: 100%;
        padding: 0.375rem 0.75rem;
        font-size: 1rem;
        font-weight: 400;
        line-height: 1.5;
        color: #495057;
        background-color: #fff;
        background-clip: padding-box;
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
        margin-bottom: 10px;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }

    #search-bar.visible {
        display: block;
    }

    #search-bar:focus {
        color: #495057;
        background-color: #fff;
        border-color: #80bdff;
        outline: 0;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    .dropdown-container {
        display: none; /* Initially hidden */
        position: relative;
        background-color: #fff;
        border: 1px solid #ccc;
        padding: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        z-index: 1000;
        margin-top: 10px;
    }
    #dropdown {
        list-style: none;
        margin: 0;
        padding: 0;
        max-height: 200px;
        overflow-y: auto;
        border-top: 1px solid #ccc;
    }

    .dropdown-container ul {
        display: none;
        margin: 0;
        padding: 0;
        list-style: none;
        max-height: 200px;
        overflow-y: auto;
        width: 100%;
        position: relative;
        padding-right: 40px; /* Space for the "X" button */
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
        cursor: pointer;
        align-items: center;
    }
    .checkbox-item:hover {
        background-color: #f9f9f9;
    }

    .remove-item {
        cursor: pointer;
        color: red;
        font-size: 14px;
    }
    .search-bar-container {
        position: relative;
        width: 100%;
    }

    #search-bar {
        display: block;
        width: 100%;
        padding: 0.375rem 0.75rem;
        font-size: 1rem;
        font-weight: 400;
        line-height: 1.5;
        color: #495057;
        background-color: transparent; /* No background color */
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        padding-left: 30px; /* Adds space for the icon */
    }

    #search-bar::placeholder {
        padding-left: 5px; /* Moves the placeholder text 5px to the right */
    }

    .search-icon {
        position: absolute;
        left: 10px; /* Position the icon on the left inside the input */
        top: 50%;
        transform: translateY(-50%);
        font-size: 1.2rem;
        color: #495057;
    }
</style>








