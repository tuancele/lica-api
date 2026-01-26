<?php

declare(strict_types=1);
    echo $menu;
?>
<script type="text/javascript">
    $(document).ready(function () {
        $('.sortable').nestedSortable({
            handle: 'div',
            items: 'li',
            toleranceElement: '> div'
        });
    });
</script>  
