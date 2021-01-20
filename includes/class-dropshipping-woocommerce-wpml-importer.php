<?php
/**
 * Knawat MP Importer class.
 *
 * @link       http://knawat.com/
 * @since      1.0.0
 * @category   Class
 * @author     Suraj Rathod
 *
 * @package    Knawat_Dropshipping_wmpl_Woocommerce
 * @subpackage Knawat_Dropshipping_wmpl_Woocommerce/includes
 */
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Include dependencies.
 */
if ( ! class_exists( 'WCML_Editor_UI_Product_Job', false ) ) {
	include_once WCML_PLUGIN_PATH.'/inc/translation-editor/class-wcml-editor-ui-product-job.php';
}

if ( class_exists( 'WCML_Editor_UI_Product_Job', false ) ) :

	/**
	 * Knawat_Dropshipping_wmpl_Woocommerce_Importer Class.
	 */
	class Knawat_Dropshipping_wmpl_Woocommerce_Importer extends WCML_Editor_UI_Product_Job{

		
		/**
		 * __construct function.
		 *
		 * @access public
		 * @return void
		 */
		public function __construct( ) {
				add_action( 'wpml_import',array($this,'get_product_formated'), 10, 2);
		}

	

		public function get_product_formated($product_ID,$single_product){
			global $sitepress,$wpdb,$woocommerce_wpml;

			$single_productData 	= array();
			$another_language   	= array();
			$current_lng_code   	= ICL_LANGUAGE_CODE;
			$language_info			= icl_get_languages();
			$language_count			= count($language_info);
			$active_language    	= $language_info[$current_lng_code];
			$language_details  	  	= apply_filters( 'wpml_post_language_details', NULL, $product_ID ) ;
			$active_language_code 	= $language_details['language_code'];
			$categories 			= $single_product->categories;
			$main_title				= $single_product->name->$active_language_code;

			if(!empty($current_lng_code) && $language_count == 2){
				unset($language_info[$active_language_code]);
				
				if (version_compare(PHP_VERSION, '7.3.0') >= 0):
					$another_language = array_key_first($language_info);
				else:
					$another_language = array_keys($language_info);
					$another_language = $another_language[0];
				endif;
			}

			
			$categories_data 									= $this->product_taxonomy_data( $categories , $active_language_code , $another_language );
			$single_productData['ID']   	  					= $product_ID;
			$single_productData['sku']   	  					= $single_product->sku;
			$single_productData[md5('title')] 					= $single_product->name->$another_language;
			
			$single_productData[md5('slug')]  					= '';
			$single_productData[md5('product_excerpt')]  		= '';

			$single_productData[md5('post_content')] 			= $single_product->description->$another_language;
			$single_productData['name'] 	  					= $single_product->name->$another_language;
			$jobID												= $this->get_job_id($main_title);
			$job_details['job_id']			  					= $product_ID;
			$job_details['target']			  					= $another_language;
			$job_details['job_type']			  				= 'post_product';
			
			$postData											= 'job_post_id='.$product_ID.'&job_id='.$jobID.'&'.$categories_data;
			$_POST['data'] 										= $postData;
			
		
			$save_product	= new WCML_Editor_UI_Product_Job($job_details, $woocommerce_wpml, $sitepress, $wpdb);
			$save_product->save_translations($single_productData);
			
		}


		public function product_taxonomy_data($categories,$current_lng_code,$another_language){
			if(!empty($categories)){
				$taxonomy_array					= array();
				$taxonomy_string				= '';
				foreach($categories as $category){
						$category_name 			= $category->name->$current_lng_code;
						$category_trans_name 	= $category->name->$another_language;
						$category_treeNodeLevel = $category->treeNodeLevel;
						$term_data 				= get_term_by('name',$category_name,'product_cat');
						
						if(!empty($term_data)){
							
								$term_id = $term_data->term_id;
								// taxonomy array 
								$taxonomy_array['fields']['t_'.$term_id]['data']		= $category_trans_name;
								$taxonomy_array['fields']['t_'.$term_id]['tid']			= 0;
								$taxonomy_array['fields']['t_'.$term_id]['format']		= 'base64';

								// taxonomy string data as per save function 
								$taxonomy_string .= "fields[t_$term_id][data]=".$category_trans_name;
								$taxonomy_string .= "&fields[t_$term_id][tid]=0";
								$taxonomy_string .= "&fields[t_$term_id][format]=base64&";
						}
				}
				
			}
			return $taxonomy_string;
		}


		public function get_job_id($main_title){
			if(!empty($main_title)){
				global $wpdb;
				$pattern 	= "/'/i";
				$main_title = preg_replace($pattern, "\'", $main_title);
				$jobID 		= $wpdb->get_row("SELECT * FROM {$wpdb->prefix}icl_translate_job WHERE `title` LIKE '".$main_title."' ");
				$job_id		= $jobID->job_id;
			}
			return $job_id;
		}
	}

endif;
