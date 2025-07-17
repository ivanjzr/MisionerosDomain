<?php
/**
 * Genera la navegación secundaria para las secciones
 */
function renderSecondaryNavigation($app_instance, $user_info, $options = [], $custom_left_html = '', $custom_right_html = '') {
    //dd($user_info); 
    
    //
    function getMenusList($sub_sections) {
        $config_menus = [];        
        if ($sub_sections) {
            foreach ($sub_sections as $sub) {
                if ($sub['is_menu_expandible']){
                    $config_menus[] = [
                        'name' => $sub['nombre'],
                        'url' => '/admin/' . str_replace('.', '/', $sub['model_name']) . '/index',
                        'icon' => $sub['fa_icon']
                    ];
                }
                
            }
        }
        return $config_menus;
    }
    //
    function getIndividualMenus($sub_sections) {
        $config_menus = [];        
        if ($sub_sections) {
            foreach ($sub_sections as $sub) {
                if (!$sub['is_menu_expandible']){
                    $config_menus[] = [
                        'name' => $sub['nombre'],
                        'url' => '/admin/' . str_replace('.', '/', $sub['model_name']) . '/index',
                        'icon' => $sub['fa_icon']
                    ];
                }
                
            }
        }
        return $config_menus;
    }
    
    //
    $menus_list = [];
    $individual_menus = [];
    //
    if (isset($user_info['sub_menus']) && is_array($user_info['sub_menus'])){
        $menus_list = getMenusList($user_info['sub_menus']);
        $individual_menus = getIndividualMenus($user_info['sub_menus']);
    }
    

    
    // Opciones por defecto
    $defaults = [
        'dropdown_title' => 'Catalogos',
        'home_url' => '/admin/home'
    ];
    
    $options = array_merge($defaults, $options);
    
    ob_start();
    ?>
    <!-- Secondary Navigation -->
    <div class="bg-light pt-2 mb-0">
        <div class="container-fluid">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <!-- Left: Breadcrumb & Title -->
                        <div class="col-md-8 col-12">
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-2">
                                    <li class="breadcrumb-item">
                                        <a href="<?= $options['home_url']; ?>" class="text-decoration-none">
                                            <i class="fas fa-home me-1"></i>Home
                                        </a>
                                    </li>
                                    <li class="breadcrumb-item">
                                        <?= $app_instance::getSectionTitle(); ?>
                                    </li>
                                </ol>
                            </nav>
                            <h4 class="mb-0 fw-bold">
                                <i class="fas <?= $user_info['main_section']['fa_icon']; ?> me-2"></i>
                                <?= $app_instance::getSectionTitle(); ?>
                            </h4>
                        </div>
                        
                        <!-- Right: Config Menu -->
                        <div class="col-md-4 col-12 text-md-end">

                            <?php if (count($individual_menus) > 0): ?>
                                <?php foreach ($individual_menus as $menu): ?>

                                <a class="btn btn-primary btnReload" href="<?= $menu['url']; ?>">
                                    <i class="fas <?= $menu['icon']; ?> me-2"></i>
                                    <?= $menu['name']; ?>
                                </a>

                                <?php endforeach; ?>
                            <?php endif; ?>

                            <?php if (!empty($custom_left_html)): ?>
                                <?= $custom_left_html; ?>
                            <?php endif; ?>

                            <?php if (count($menus_list) > 0): ?>
                            <div class="dropdown d-inline-block">
                                <button class="btn btn-outline-secondary dropdown-toggle" type="button" 
                                        id="configDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-list me-2"></i>
                                    <span class="d-none d-sm-inline"><?= $options['dropdown_title']; ?></span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="configDropdown">
                                    <li>
                                        <h6 class="dropdown-header">
                                            <i class="fas <?= $user_info['current']['fa_icon']; ?> me-2"></i>
                                            <?= $user_info['current']['nombre']; ?>
                                        </h6>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <?php foreach ($menus_list as $menu): ?>
                                        <li>
                                            <a class="dropdown-item" href="<?= $menu['url']; ?>">
                                                <i class="fas <?= $menu['icon']; ?> me-2"></i>
                                                <?= $menu['name']; ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>

                             <?php if (!empty($custom_right_html)): ?>
                                <?= $custom_right_html; ?>
                            <?php endif; ?>


                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    
    <?php
    
    return ob_get_clean();
}
?>