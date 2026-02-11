<!-- Filter Sidebar -->
<div class="spacart-filter-sidebar">

    <!-- Subcategories -->
    <?php if (!empty($subcategories)) { ?>
    <div class="spacart-filter-section">
        <h6>Catégories</h6>
        <ul class="spacart-filter-list">
            <?php foreach ($subcategories as $sub) { ?>
            <li>
                <a href="#/category/<?php echo $sub->id; ?>" class="spacart-spa-link <?php echo (!empty($filters['category_id']) && $filters['category_id'] == $sub->id) ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($sub->label); ?>
                    <span class="filter-count">(<?php echo $sub->product_count; ?>)</span>
                </a>
            </li>
            <?php } ?>
        </ul>
    </div>
    <?php } ?>

    <!-- Brands -->
    <?php if (!empty($brands)) { ?>
    <div class="spacart-filter-section">
        <h6>Marques</h6>
        <ul class="spacart-filter-list">
            <?php foreach ($brands as $brand) { ?>
            <li>
                <a href="#/brands/<?php echo $brand->id; ?>" class="spacart-spa-link <?php echo (!empty($filters['brand']) && $filters['brand'] == $brand->id) ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($brand->label); ?>
                    <span class="filter-count">(<?php echo $brand->product_count; ?>)</span>
                </a>
            </li>
            <?php } ?>
        </ul>
    </div>
    <?php } ?>

    <!-- Price Range -->
    <div class="spacart-filter-section">
        <h6>Prix</h6>
        <form id="spacart-price-filter" class="spacart-price-range">
            <div class="row" style="margin-bottom:0;">
                <div class="input-field col s6" style="margin-top:0;">
                    <input type="number" id="spacart-price-min" name="price_min" placeholder="Min" step="0.01"
                           value="<?php echo !empty($filters['price_min']) ? htmlspecialchars($filters['price_min']) : ''; ?>">
                </div>
                <div class="input-field col s6" style="margin-top:0;">
                    <input type="number" id="spacart-price-max" name="price_max" placeholder="Max" step="0.01"
                           value="<?php echo !empty($filters['price_max']) ? htmlspecialchars($filters['price_max']) : ''; ?>">
                </div>
            </div>
            <button type="submit" class="btn btn-small btn-flat" style="width:100%;">
                <i class="material-icons left">filter_list</i>Filtrer
            </button>
        </form>
    </div>

    <!-- In Stock -->
    <div class="spacart-filter-section">
        <label>
            <input type="checkbox" class="filled-in" id="spacart-filter-stock"
                   <?php echo !empty($filters['in_stock']) ? 'checked' : ''; ?>
                   onchange="var h=window.location.hash;var u=new URL('http://x'+h.substr(1));if(this.checked)u.searchParams.set('in_stock','1');else u.searchParams.delete('in_stock');window.location.hash='#'+u.pathname+u.search;">
            <span>En stock uniquement</span>
        </label>
    </div>

</div>
