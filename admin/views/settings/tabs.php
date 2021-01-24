<div class="cheetaho-tabs">
    <ul>
        <li <?php echo (!isset($_GET['tab']) || (isset($_GET['tab']) && $_GET['tab'] == 'general') ? 'class="active"' : '') ?>>
            <a href="#general">General</a>
        </li>
        <li <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'advanced' ? 'class="active"' : '') ?>>
            <a href="#advanced">Advanced</a>
        </li>
        <li <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'resources' ? 'class="active"' : '') ?>>
            <a href="#resources">WP Resources</a>
        </li>
    </ul>
</div>
