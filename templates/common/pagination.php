<!-- Pagination -->
<?php if ($total_pages > 1) { ?>
<div class="spacart-pagination">
    <ul class="pagination">
        <!-- Previous -->
        <li class="<?php echo $current_page <= 1 ? 'disabled' : 'waves-effect'; ?>">
            <?php if ($current_page > 1) { ?>
                <a href="#!" class="spacart-page-link" data-page="<?php echo $current_page - 1; ?>">
                    <i class="material-icons">chevron_left</i>
                </a>
            <?php } else { ?>
                <a href="#!"><i class="material-icons">chevron_left</i></a>
            <?php } ?>
        </li>

        <?php
        // Show page numbers with ellipsis
        $start = max(1, $current_page - 2);
        $end = min($total_pages, $current_page + 2);

        if ($start > 1) {
            echo '<li class="waves-effect"><a href="#!" class="spacart-page-link" data-page="1">1</a></li>';
            if ($start > 2) {
                echo '<li class="disabled"><a href="#!">...</a></li>';
            }
        }

        for ($i = $start; $i <= $end; $i++) {
            if ($i == $current_page) {
                echo '<li class="active"><a href="#!">'.$i.'</a></li>';
            } else {
                echo '<li class="waves-effect"><a href="#!" class="spacart-page-link" data-page="'.$i.'">'.$i.'</a></li>';
            }
        }

        if ($end < $total_pages) {
            if ($end < $total_pages - 1) {
                echo '<li class="disabled"><a href="#!">...</a></li>';
            }
            echo '<li class="waves-effect"><a href="#!" class="spacart-page-link" data-page="'.$total_pages.'">'.$total_pages.'</a></li>';
        }
        ?>

        <!-- Next -->
        <li class="<?php echo $current_page >= $total_pages ? 'disabled' : 'waves-effect'; ?>">
            <?php if ($current_page < $total_pages) { ?>
                <a href="#!" class="spacart-page-link" data-page="<?php echo $current_page + 1; ?>">
                    <i class="material-icons">chevron_right</i>
                </a>
            <?php } else { ?>
                <a href="#!"><i class="material-icons">chevron_right</i></a>
            <?php } ?>
        </li>
    </ul>
    <p class="grey-text center-align" style="font-size:0.85rem;">
        Page <?php echo $current_page; ?> sur <?php echo $total_pages; ?> (<?php echo $total; ?> produits)
    </p>
</div>
<?php } ?>
