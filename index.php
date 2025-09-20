<!DOCTYPE html>
<html lang="<?php echo isset($_SESSION['lang']) ? $_SESSION['lang'] : 'en'; ?>">

<?php 
// 1. ตั้งค่าตัวแปรสำหรับเมนู active และเรียกใช้ header
// header.php ที่แก้ไขแล้วจะจัดการเรื่องภาษาให้เอง
$mode = 'home';
include 'header.php'; 
?>
<style>
    /* --- Your existing CSS styles --- */
    #hero.section { height: auto !important; padding: 0 !important; min-height: 0 !important; }
    #hero.section img { width: 100%; height: auto; display: block; }
    .search-bar .search-input { width: 100%; max-width: 600px; margin: 20px auto; padding: 10px 15px; border: 2px solid #b08b5b; border-radius: 8px; background: #fff; display: flex; gap: 5px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); transition: transform .3s; }
    @media (max-width: 576px) { .search-bar .search-input { margin: 20px 10px; } }
    .search-bar .search-input:hover { transform: translateY(-5px); }
    .search-bar input { flex: 1; border: none; outline: none; } 
</style>

<main class="main">
    <section id="hero">
        <img src="assets/img/theprestige-2.png" class="img-fluid w-100" alt="The Prestige Living">
    </section>

    <section id="search" class="services section mb-5">
        <div class="search-bar">
            <div class="search-input">
                <button id="searchButton" class="btn"><i class="bi bi-search"></i></button>
                <input id="searchInput" type="text" placeholder="<?php echo $lang['search_placeholder']; ?>">
                <button class="clear-button btn">x</button>
                <button id="filterButton" class="btn filter-button" data-bs-toggle="modal" data-bs-target="#filterModal">
                    <i class="bi bi-funnel"></i> <?php echo $lang['filter_button']; ?>
                </button>
            </div>
        </div>
    </section>
</main>

<div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="filterModalLabel"><?php echo $lang['filter_modal_title']; ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="filter-group">
                    <label><?php echo $lang['property_type_label']; ?></label>
                    <select class="form-select" id="type" name="type">
                        <option value=""><?php echo $lang['all_types']; ?></option>
                        <option value="H"><?php echo $lang['house']; ?></option>
                        <option value="C"><?php echo $lang['condo']; ?></option>
                        <option value="V"><?php echo $lang['villa']; ?></option>
                        <option value="T"><?php echo $lang['townhouse']; ?></option>
                        <option value="L"><?php echo $lang['land']; ?></option>
                        <option value="A"><?php echo $lang['apartment']; ?></option>
                    </select>
                </div>
                <div class="filter-group">
                    <label><?php echo $lang['price_label']; ?></label>
                    <div class="price-range">
                        <input type="number" class="form-control" placeholder="<?php echo $lang['min_price_placeholder']; ?>" id="minPrice" name="minPrice"> - 
                        <input type="number" class="form-control" placeholder="<?php echo $lang['max_price_placeholder']; ?>" id="maxPrice" name="maxPrice">
                    </div>
                </div>
                <div class="filter-group">
                    <label><?php echo $lang['bedrooms_label']; ?></label>
                    <select class="form-select" id="roomQty" name="roomQty">
                        <option value=""><?php echo $lang['any_bedrooms']; ?></option>
                        <option value="1"><?php echo $lang['1_bedroom']; ?></option>
                        <option value="2"><?php echo $lang['2_bedrooms']; ?></option>
                        <option value="3"><?php echo $lang['3_bedrooms']; ?></option>
                        <option value="4"><?php echo $lang['4_bedrooms']; ?></option>
                        <option value="5"><?php echo $lang['5_plus_bedrooms']; ?></option>
                    </select>
                </div>
                <div class="filter-group">
                    <label><?php echo $lang['usable_area_label']; ?></label>
                    <div class="area-range">
                        <input type="number" class="form-control" placeholder="<?php echo $lang['min_area_placeholder']; ?>" id="minSize" name="minSize"> - 
                        <input type="number" class="form-control" placeholder="<?php echo $lang['max_area_placeholder']; ?>" id="maxSize" name="maxSize">
                    </div>
                </div>
                <div class="filter-group">
                    <label><?php echo $lang['distance_from_station_label']; ?></label>
                    <select class="form-select" id="distance" name="distance">
                        <option value=""><?php echo $lang['any_distance']; ?></option>
                        <option value="0.5"><?php echo $lang['less_than_500m']; ?></option>
                        <option value="1"><?php echo $lang['less_than_1km']; ?></option>
                        <option value="1.5"><?php echo $lang['less_than_1_5km']; ?></option>
                        <option value="2"><?php echo $lang['less_than_2km']; ?></option>
                    </select>
                </div>
                <div class="filter-group">
                    <label><?php echo $lang['bathrooms_label']; ?></label>
                    <select class="form-select" id="toiletQty" name="toiletQty">
                        <option value=""><?php echo $lang['any_bathrooms']; ?></option>
                        <option value="1"><?php echo $lang['1_bathroom']; ?></option>
                        <option value="2"><?php echo $lang['2_bathrooms']; ?></option>
                        <option value="3"><?php echo $lang['3_bathrooms']; ?></option>
                        <option value="4"><?php echo $lang['4_bathrooms']; ?></option>
                        <option value="5"><?php echo $lang['5_plus_bathrooms']; ?></option>
                    </select>
                </div>
                <div class="filter-group">
                    <label><?php echo $lang['highlights_label']; ?></label>
                    <div id="rentFacilitiesCombo" class="feature-buttons"></div>
                </div>
                <div class="filter-group">
                    <label><?php echo $lang['facilities_label']; ?></label>
                    <div id="rentFacilitiesFCombo" class="feature-buttons"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo $lang['cancel_button']; ?></button>
                <button type="button" class="btn btn-primary"><?php echo $lang['apply_filters_button']; ?></button>
            </div>
        </div>
    </div>
</div>

<section id="services" class="py-5">
    <div class="container">
        <div class="row gy-4 search-results-info" data-aos="fade-up" data-aos-delay="100">
            </div>
    </div>
</section>

<script>
    window.searchTranslations = {
        no_results_found: "<?php echo addslashes($lang['no_results_found']); ?>",
        price_on_request: "<?php echo addslashes($lang['price_on_request']); ?>"
    };
</script>
<?php include 'footer.php'; ?>
<script src="assets/js/search.js"></script>

</html>