<?php
   /*
   Plugin Name: woocommerce edite price
   Plugin URI: https://persianap.ir/wordpress-plugins/woocomerce_price_editor
   description: This plugin gives you moderator page to edit multiple product price in same page
   This plugin can use to fast edite multiple product price in single page
   Version: 1.0.3
   Author: Farshid mousavi +989021233212
   Author URI: http://kaati-agency.ir
   License: GPL
   License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html
   Text Domain: woocommerce_price_editor
   Domain Path: /languages
   */
  //add text domain
 function plugin_load_textdomain() {
    load_plugin_textdomain( 'woocommerce_price_editor', false, basename( dirname( __FILE__ ) ) . '/languages/' );
   } 
   add_action( 'init', 'plugin_load_textdomain' );

   if (! defined('ABSPATH')){
      die;
   }

   //delete product on click delete link
   if(isset($_GET['delete'])){
       $id = (int)$_GET['delete'];
        wp_delete_post( $id );
   }

   //save data to database
   if(isset($_POST['save'])){
        //save products changes
       foreach($_POST as $key => $value){
        if(substr( $key, 0, 2 ) === "a_"){
            update_post_meta(substr($key, 2), '_sale_price', $value);
            update_post_meta(substr($key, 2), '_price', $value);
        }
        if(substr( $key, 0, 2 ) === "b_"){
            update_post_meta(substr($key, 2), '_regular_price', $value);
        }
       }
       add_action( 'woocommerce_process_product_meta',  'WC_Meta_Box_Product_Data::save',  10,  2 ); 
       wp_reset_query(); 
   }

//add custom style to admin header
add_action('admin_head', 'my_custom_style');
function my_custom_style() {
  echo '<style>
  .col { width: 20% !important; text-align:center; }
  .thead-dark{background-color:black; color:white;}
  .col-1{width:10%;}
  .col-1 img{width:50px;height:50px;}
  .table td, .table th {
    padding: .75rem;
    vertical-align: top;
    border-top: 1px solid #dee2e6;
    }

    .table tr:hover{
        background-color:gray;
        color:white;
    }
    .table tr:hover a{
        color:white;
    }
    .table a{text-decoration:none;}
    .tcd_123{
      padding: 10px;
    }
    .page-numbers{
      padding: 10px;
      background-color: #007cba;
      color:white;
      font-size: 16px;
      border-radius:3px;
    }
    .displayCatName{
      font-weight: bold;
      font-size: 20px;
      padding-bottom: 20px;
      padding-top: 20px;
    }
  </style>';
}

//get category list
function getParentCategories() {
  global $wpdb;
  $sql = "SELECT term_id as id, name, slug FROM wp_terms where term_id in (SELECT term_id FROM wp_term_taxonomy where parent = 0 and taxonomy = 'product_cat') order by name asc";
  $parents = $wpdb->get_results( $sql );
  return $parents;
}
//get category chiled list
function getChildCategories($id) {
  global $wpdb;
  $sql = "SELECT term_id as id, name, slug FROM wp_terms where term_id in (SELECT term_id FROM wp_term_taxonomy where parent = $id and taxonomy = 'product_cat')";
  $children = $wpdb->get_results( $sql );
  return $children;
}

function getCurrentCategory($id){
  global $wpdb;
  $sql = "SELECT term_id as id, name, slug FROM wp_terms where term_id in (SELECT term_id FROM wp_term_taxonomy where slug = '$id' and taxonomy = 'product_cat')";
  $children = $wpdb->get_row( $sql );
  return $children;
}

//add to admin menu
   function admin_menu_page()
   {
       add_menu_page( __('ADMINMENU_NAME', 'woocommerce_price_editor'), __('ADMINMENU_NAME', 'woocommerce_price_editor'), 'manage_options', 'edite_products', 'optionsView' );
   }
   add_action( 'admin_menu', 'admin_menu_page' );
   
   //option page html
   function optionsView()
   {
        echo'        
        <h3>'. __('ADMINPAGETITLE_NAME', 'woocommerce_price_editor').'</h3>
        <form method="get">
        <input type="hidden" id="page" name="page" value="edite_products">
        <div class="tcd_123"><label>'.__("SELECT_CATEGOTY", "woocommerce_price_editor").'</label>
        <select name="category_id" id="category_id">
        <option value="0">همه</option>
        ';
        $data = getParentCategories();
        
        foreach($data as $pp){
          echo '
          <option value="'.$pp->slug.'">'.$pp->name.'</option>';
          $subCat = getChildCategories($pp->id);
          foreach($subCat as $sub){
            echo '<option value="'.$sub->slug.'">- '.$sub->name.'</option>';
              $sub2Cat = getChildCategories($sub->id);
              foreach($sub2Cat as $sub2){
                echo '<option value="'.$sub2->slug.'">-- '.$sub2->name.'</option>';
                $sub3Cat = getChildCategories($sub2->id);
                foreach($sub3Cat as $sub3){
                  echo '<option value="'.$sub3->slug.'">-- '.$sub3->name.'</option>';
                }
              }
          }
        }
        echo '
        </select>
        <label for="perPage">'.__("PERPAGE_LABEL", "woocommerce_price_editor").'</label>
        <select name="perPage" id="perPage">
          <option value="10">10</option>
          <option value="20">20</option>
          <option value="50">50</option>
          <option value="100">100</option>
        </select>
        </form>
        <input type="submit" value="'.__("APLLY_FILTERS", "woocommerce_price_editor").'" class="button-primary" />
        </div>
        ';
      //per page
      if(isset($_GET['perPage'])){
        $perPage = (int)$_GET['perPage'];
      }else{
        $perPage = 20;
      }
      //pagination 
      if(isset($_GET['paged'])){
        $paged = (int)$_GET['paged'];
      }else{
        $paged = 1;
      }
    //check if filter set
    if(isset($_GET['category_id']) && !$_GET['category_id'] == 0){
      $cat = $_GET['category_id'];
      //set filters on product display
      $args = array(
        'post_type'      => 'product',
        'paged'          => $paged,
        'posts_per_page' => $perPage,
        'tax_query' => array(
          array(
              'taxonomy' => 'product_cat',
              'field'    => 'slug',
              'terms'    => array( $cat ),
          ),
      ),
    );
    }else{
      $args = array(//show all products
        'post_type'      => 'product',
        'paged'          => $paged,
        'posts_per_page' => $perPage,
      );
    }
    
    //get all products    
    $loop = new WP_Query( $args ); 
       
?>
<div class='displayCatName'><?php echo _e("CURRENT_CATEGORY", "woocommerce_price_editor");?> = <?php if(isset($_GET['category_id']) && !$_GET['category_id'] == 0){ echo getCurrentCategory($_GET['category_id'])->name;}else{  echo _e("ALL", "woocommerce_price_editor");} ?></div>
<form method="post" names="saveDDD">
<table class="table">
  <thead class="thead-dark">
    <tr>
      <th class="col-1"><?php echo _e("ROW_NUM", "woocommerce_price_editor");?></th>
      <th class="col-1"><?php echo _e("IMAGE_FILD", "woocommerce_price_editor");?></th>
      <th class="col"><?php echo _e("PRODUCT_NAME", "woocommerce_price_editor"); ?></th>
      <th class="col"><?php echo _e("SELL_PRICE", "woocommerce_price_editor");?></th>
      <th class="col"><?php echo _e("RE_PRICE", "woocommerce_price_editor");?></th>
      <th class="col">-</th>
    </tr>
  </thead>
  <tbody>
<?php
//display products on admin page
$n = 1;
if ( $loop->have_posts()){
while ( $loop->have_posts() ) : $loop->the_post();
    global $product;
    echo '
    <tr>
      <td class="col-1" scope="row">'.$n.'</td>
      <td class="col-1" >'. woocommerce_get_product_thumbnail().'</td>
      <td class="col" > <a href="'.get_permalink().'">'.get_the_title().'</a></td>
      <td class="col" ><input type="text" id=a_'.get_the_ID().' name="a_'.get_the_ID().'" value="'.get_post_meta( get_the_ID(), '_sale_price', true ).'"></td>
      <td class="col" ><input type="text" id=b_'.get_the_ID().' name="b_'.get_the_ID().'" value="'.get_post_meta( get_the_ID(), '_regular_price', true ).'"></td>
      <td class="col"><a href="?page=edite_products&delete='.get_the_ID().'">'.__("DELETE_BTN", "woocommerce_price_editor").'</a></td>
      </tr>
  ';
  $n++;
  
endwhile;
//ext_posts_link( 'Older Entries »', $query->max_num_pages() );
}
?>
</tbody>
</table>
</br>
<div class='pagination'>
<?php 
//pagination
$big = 999999999; // need an unlikely integer
echo paginate_links( array(
    'base' => str_replace( $big, '%#%', get_pagenum_link( $big, false ) ),
    'format' => '?paged=%#%',
    'current' => max( 1, $paged ),
    'total' => $loop->max_num_pages,
        'before_page_number' => '<span class="screen-reader-text">'.__( 'Page', 'woocommerce_price_editor' ).' </span>'
) );
?>
</div>
</br>
<input type="submit" name="save" value="<?php echo _e("SAVE_FORM", "woocommerce_price_editor");?>" class="button-primary" />
<!-- <?php
submit_button();
?> -->
    </form>
<?php
wp_reset_query();    
   }