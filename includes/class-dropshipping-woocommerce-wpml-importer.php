<?php
/**
 * Knawat MP Importer class.
 *
 * @link       http://knawat.com/
 * @since      1.0.0
 * @category   Class
 * @author     Suraj Rathod
 *
 * @package    Dropshipping_Woocommerce_WPML_Addon
 * @subpackage Dropshipping_Woocommerce_WPML_Addon/includes
 */
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Include dependencies.
 */
if ( ! class_exists( 'WCML_Editor_UI_Product_Job', false ) ) {
	include_once WCML_PLUGIN_PATH . '/inc/translation-editor/class-wcml-editor-ui-product-job.php';
}

if ( class_exists( 'WCML_Editor_UI_Product_Job', false ) ){

	/**
	 * Knawat_Dropshipping_wpml_Woocommerce_Importer Class.
	 */
	class Knawat_Dropshipping_wpml_Woocommerce_Importer extends WCML_Editor_UI_Product_Job {


		/**
		 * __construct function.
		 *
		 * @access public
		 * @return void
		 */
		public function __construct( ) {
			add_action( 'wpml_import_translation_product',array($this,'get_product_formated'), 10, 2);
			add_action( 'remove_stokout_product',array($this,'remove_outof_stock'), 10, 1);
		}


		/**
		 * Convert product translation data
		 */
		public function get_product_formated( $product_ID, $single_product ) {

			/**
			 * Remove Fetuared Image By Plugin Action
			 */

			add_filter( "option_knawatfibu_options", "wpml_dropship_disable_fibu_plugin",  99, 2 );


			if ( ! empty( $product_ID ) ) {

				global $sitepress;

				$activeLanguages			= icl_get_languages();
				$defaultLang	= $sitepress->get_default_language();
				unset($activeLanguages[$defaultLang]);
				
				$categories			= $single_product->categories;
				$attributes			= $single_product->attributes;
				$tids				= $this->get_translation_id($sitepress,$product_ID);
				$import_prd_lang 	= array_keys((array)$single_product->name);

				if(!empty($activeLanguages)){

					$single_productData 	= array();

					foreach($activeLanguages as $lang_key => $lang_info){

						if(in_array($lang_key,$import_prd_lang)){
							$name = isset($single_product->name->$lang_key)?$single_product->name->$lang_key:reset($single_product->name);
							$post_title 								= sanitize_text_field($name);
							$post_title 								= iconv(mb_detect_encoding($post_title),'UTF-8',$post_title);
							$single_productData[md5('title')]= $post_title;
							$single_productData[md5('slug')]			= sanitize_title($post_title);

							$post_content								= isset($single_product->description->$lang_key)?$single_product->description->$lang_key:reset($single_product->description);
							$post_content 								= iconv(mb_detect_encoding($post_content),'UTF-8',$post_content);
							$single_productData[md5('product_content')]	= iconv('UTF-8','ASCII//TRANSLIT',$post_content);
							$single_productData[md5('product_excerpt')] = '';

							$jobID										= $this->get_job_id($lang_key,$tids);
							
							$job_details['job_id']						= $product_ID;
							$job_details['job_type']					= 'post_product';
							$job_details['job_post_id']					= $product_ID;
							$job_details['target']						= $lang_key;
							$job_details['source_lang']					= $defaultLang;
							$job_details['job_post_type']				= 'post_product';
							
							$categories_data_array 						= $this->product_taxonomy_data_array($categories,$defaultLang,$lang_key);
							$attributes_data_array 						= $this->product_attributes_data_array($product_ID,$attributes,$defaultLang,$lang_key);
							$merge_data									=  array_merge($single_productData,$categories_data_array,$attributes_data_array);
							
							$categories_data 					= $this->product_taxonomy_data($categories,$defaultLang,$lang_key);
							$attributes_data 					= $this->product_attributes_data($product_ID,$attributes,$defaultLang,$lang_key);
							$post_data_string					= $this->post_data_string($single_product,$product_ID,$jobID,$attributes_data,$categories_data,$defaultLang,$lang_key);
							
							$_POST['data']							= $post_data_string;
							

							$data      = [];
							$post_data = \WPML_TM_Post_Data::strip_slashes_for_single_quote( $_POST['data'] );
							parse_str( $post_data, $data );
							
							$data = apply_filters( 'wpml_translation_editor_save_job_data', $data );
							
							$job = \WPML\Container\make( \WPML_TM_Editor_Job_Save::class );
							
							$job_details = [
								'job_type'             => $data['job_post_type'],
								'job_id'               => $data['job_post_id'],
								'target'               => $data['target_lang'],
								'translation_complete' => isset( $data['complete'] ) ? true : false,
							];
							$job         = apply_filters( 'wpml-translation-editor-fetch-job', $job, $job_details );
							
							$job->save( $data );
							
						}
					}

						$merge_data 		= array_merge([],$single_productData,$categories_data_array,$attributes_data_array);
						$this->save_translations($merge_data);
				}
			}
		}

		/**
		 * Create product category/taxonomy string
		 */
		public function product_taxonomy_data( $categories, $defaultLang, $lang_key ) {
			if ( ! empty( $categories ) ) {
				$taxonomy_string = '';
				foreach ( $categories as $category ) {

						$category_name          = isset($category->name->$defaultLang)?$category->name->$defaultLang:reset($category->name);
						$category_trans_name    = isset($category->name->$lang_key)?$category->name->$lang_key:reset($category->name);
						$category_treeNodeLevel = $category->treeNodeLevel;
						$term_data              = get_term_by( 'name', $category_name, 'product_cat' );

					if ( ! empty( $term_data ) ) {
							$term_id = $term_data->term_id;
							// taxonomy string data as per save function
							$taxonomy_string .= "fields[t_$term_id][data]=" . $category_trans_name;
							$taxonomy_string .= "&fields[t_$term_id][tid]=0";
							$taxonomy_string .= "&fields[t_$term_id][format]=base64&";
					}
				}
			}
			return $taxonomy_string;
		}


		/**
		 * Create product category/taxonomy array
		 */
		public function product_taxonomy_data_array( $categories, $defaultLang, $lang_key ) {
			if ( ! empty( $categories ) ) {
				$taxonomy_array = array();
				foreach ( $categories as $category ) {

						$category_name          = isset($category->name->$defaultLang)?$category->name->$defaultLang:reset($category->name);
						$category_trans_name    = isset($category->name->$lang_key)?$category->name->$lang_key:reset($category->name);
						$category_treeNodeLevel = $category->treeNodeLevel;
						$term_data              = get_term_by( 'name', $category_name, 'product_cat' );

					if ( ! empty( $term_data ) ) {
							$term_id = $term_data->term_id;
							
							$taxonomy_array[md5('t_'.$term_id)]  .= $category_trans_name;
					}
				}
				
			}
			return $taxonomy_array;
		}



		/**
		 * Create product attribute/variation string.
		 */
		public function product_attributes_data( $product_ID, $attributes_list, $defaultLang, $lang_key ) {
			$variation_string = '';
			$attribute_string = '';
			global $product;

			if ( ! empty( $attributes_list ) ) {
				foreach ( $attributes_list as $attributes ) {
					$name = isset($attributes->name->$defaultLang)?$attributes->name->$defaultLang:reset($attributes->name);
					$att_name        = sanitize_title( $name );
					$att_format_name = sanitize_title( $name ) . '_name';
					$att_trans_name  = isset($attributes->name->$lang_key)?$attributes->name->$lang_key:reset($attributes->name);
					$attr_options    = $attributes->options;

					$attribute_string .= "fields[$att_format_name][data]=" . $att_trans_name;
					$attribute_string .= "&fields[$att_format_name][tid]=0";
					$attribute_string .= "&fields[$att_format_name][format]=base64&";

					if ( ! empty( $attr_options ) ) {
						foreach ( $attr_options as $key => $variation ) {

								$att_tran_value = $variation->$lang_key;
								$att_orig_value = $variation->$defaultLang;
								$att_var_data   = get_term_by( 'name', $att_orig_value, 'pa_' . $att_name );

								$attribute_string .= "fields[$att_name][data]=" . $att_tran_value;
								$attribute_string .= "&fields[$att_name][tid]=0";
								$attribute_string .= "&fields[$att_name][format]=base64&";
						}
					}

					if ( ! empty( $attr_options ) ) {
						foreach ( $attr_options as $key => $variation ) {

								$att_tran_value = $variation->$lang_key;
								$att_orig_value = $variation->$defaultLang;
								$var_data       = get_term_by( 'name', $att_orig_value, 'pa_' . $att_name );

							if ( ! empty( $var_data ) ) {
								$var_id            = $var_data->term_id;
								$variation_string .= "fields[t_$var_id][data]=" . $att_tran_value;
								$variation_string .= "&fields[t_$var_id][tid]=0";
								$variation_string .= "&fields[t_$var_id][format]=base64&";
							}
						}
					}
				}
			}

			return $attribute_string . $variation_string;
		}


		/**
		 * Create product attribute/variation array.
		 */
		public function product_attributes_data_array( $product_ID, $attributes_list, $defaultLang, $lang_key ) {
			
			$variation_array = array();
			$attribute_array = array();
			global $product;

			if ( ! empty( $attributes_list ) ) {
				foreach ( $attributes_list as $attributes ) {
					$name = isset($attributes->name->$defaultLang)?$attributes->name->$defaultLang:reset($attributes->name);
					$att_name        = sanitize_title( $name );
					$att_format_name = sanitize_title( $name ) . '_name';
					$att_trans_name  = isset($attributes->name->$lang_key)?$attributes->name->$lang_key:reset($attributes->name);
					$attr_options    = $attributes->options;

					$attribute_array[md5($att_format_name)]  = $att_trans_name;

					if ( ! empty( $attr_options ) ) {
						foreach ( $attr_options as $key => $variation ) {

								$att_tran_value = isset($variation->$lang_key)?$variation->$lang_key:reset($variation);
								$att_orig_value = isset($variation->$defaultLang)?$variation->$defaultLang:reset($variation);
								$att_var_data   = get_term_by( 'name', $att_orig_value, 'pa_' . $att_name );

								$attribute_array[md5($att_name)]  = $att_tran_value;
						}
					}

					if ( ! empty( $attr_options ) ) {
						foreach ( $attr_options as $key => $variation ) {

								$att_tran_value = $variation->$lang_key;
								$att_orig_value = $variation->$defaultLang;
								$var_data       = get_term_by( 'name', $att_orig_value, 'pa_' . $att_name );

							if ( ! empty( $var_data ) ) {

								$var_id            = $var_data->term_id;
								$variation_array[md5("t_".$var_id)]  = $att_tran_value;
							}
						}
					}
				}
			}
			
			return array_merge($attribute_array,$variation_array);
		}

		/**
		 * Get the latest job id.
		 *
		 * @param string                        $lang_key product language code
		 * @param string product translation id
		 * @return int
		 * @since 1.0.0
		 */
		public function get_job_id( $lang_key, $tids ) {
			if ( ! empty( $lang_key ) ) {
				global $wpdb;
				$product_job_id = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT MAX(tj.job_id) FROM {$wpdb->prefix}icl_translate_job AS tj
									 LEFT JOIN {$wpdb->prefix}icl_translation_status AS ts
									 ON tj.rid = ts.rid WHERE ts.translation_id=%d",
						$tids[ $lang_key ]
					)
				);
				$job_id         = $product_job_id;
			}
			return $job_id;
		}


	/**
		 * Create post data string from all the data
		 */
		public function post_data_string( $single_product, $product_ID, $jobID, $attributes_data, $categories_data, $defaultLang, $lang_key ) {

			$categories_data = !empty($categories_data) ? $categories_data : "";
			$attributes_data = !empty($attributes_data) ? $attributes_data : "";
			
			$pro_title       = sanitize_text_field( $single_product->name->$lang_key );
			$pro_desc        = $single_product->description->$lang_key;
			$pro_slug        = sanitize_title( $single_product->name->$lang_key );
			$title           = 'fields[title][data]=' . $pro_title . '&fields[title][tid]=0&fields[title][format]=base64&';
			$slug            = 'fields[slug][data]=' . $pro_slug . '&fields[slug][tid]=0&fields[slug][format]=base64&';
			$product_content = 'product_content_original=&fields[product_content][data]=' . $pro_desc . '&fields[product_content][tid]=0&fields[product_content][format]=base64&';
			$product_excerpt = 'product_excerpt_original=&fields[product_excerpt][data]=&fields[product_excerpt][tid]=0&fields[product_excerpt][format]=base64&';
			$purchase_note   = 'fields[_purchase_note][data]=&fields[_purchase_note][tid]=0&fields[_purchase_note][format]=base64&';

			$starting_string = $title . $slug . $product_content . $product_excerpt . $purchase_note;
			$postData        = 'job_post_type=post_product&job_post_id=' . $product_ID . '&job_id=' . $jobID . '&source_lang=' . $defaultLang . '&target_lang=' . $lang_key . '&' . $starting_string . '&' . $attributes_data . $categories_data.'complete=on';
			
			return $postData;
		}


		/**
		 * Get translation id from Product id
		 */
		public function get_translation_id( $sitepress, $product_ID ) {
				$translated_ids = array();
			if ( ! isset( $sitepress ) ) {
				return;
			}

				$trid         = $sitepress->get_element_trid( $product_ID, 'post_product' );
				$translations = $sitepress->get_element_translations( $trid, 'product' );

			if ( ! empty( $translations ) ) {
				foreach ( $translations as $lang => $translation ) {
					$translated_ids[ $translation->language_code ] = $translation->translation_id;

				}
			}

			return $translated_ids;
		}


		/**
		 * Remove out of stock product
		 */
		public function remove_outof_stock($productID){
			try{
				if(!empty($productID)){
					$product_info = $this->product_information($productID);
					
					if(!empty($product_info)){
						foreach($product_info as $pro_info){
							$this->wc_deleteProduct($pro_info);  
						}
					}
				}

			}catch (Exception $ex) {
				//skip it
			}
		}

		/**
		 * Get product language code from ID
		 * @param productID
		 */
		public function product_language_information($productID){

			if(!empty($productID)){
				$language_code 				= ''; 
				$post_language_information	= apply_filters( 'wpml_post_language_details', NULL, $productID );
				if(!is_wp_error($post_language_information)){
					$language_code 			= $post_language_information['language_code'];
				}

			}

			return $language_code;
		}

		/**
		 * Get translated product information
		 * @param product_ID
		 */
		Public function product_information($product_ID){
			global $sitepress;
			$translated_ids = array();
			if(!isset($sitepress)) return;
			$post_id = $product_ID; // Your original product ID
			$trid = $sitepress->get_element_trid($post_id,'post_product');
			$translations = $sitepress->get_element_translations($trid,'product');
			foreach( $translations as $lang=>$translation){
				$other_ID						= $translation->element_id;
				$product_lang 					= $this->product_language_information($other_ID);
				$translated_ids[$product_lang] 	= $other_ID;
				
			}

			return $translated_ids;
		}



		/**
		* Method to delete Woo Product
		*
		* @param int $id the product ID.
		* @param bool $force true to permanently delete product, false to move to trash.
		* @return WP_Error|boolean
		*/
		public function wc_deleteProduct($product_ID){
			try{

				$product = wc_get_product($product_ID);
				if(empty($product)){
					return false;
				}
				
				if ($product->is_type('variable')){
					foreach ($product->get_children() as $child_id)
					{
						$child = wc_get_product($child_id);
						$child->delete();
					}
				}

				$product->delete(true);
				$result = $product->get_id() > 0 ? false : true;
				
				if ($parent_id = wp_get_post_parent_id($product_ID)){
					wc_delete_product_transients($parent_id);
				}
				return true;

			}catch (Exception $ex) {
					//skip it
			}

		}



}

	add_action( 'admin_init', 'WPML_Woocommerce_Importer' );
	add_action( 'init', 'WPML_Woocommerce_Importer' );
	function WPML_Woocommerce_Importer() {
		return new Knawat_Dropshipping_wpml_Woocommerce_Importer();
	}

	add_filter( 'wpml-translation-editor-fetch-job', 'wpml_dropship_disable_fibu_plugin2', 0, 2 );
}


function wpml_dropship_disable_fibu_plugin2($value, $option) {
	add_filter( "option_knawatfibu_options", "wpml_dropship_disable_fibu_plugin",  0, 2 );
	add_filter( "pre_option_knawatfibu_options", "wpml_dropship_disable_fibu_plugin",  0, 2 );
	return $value;
}

function wpml_dropship_disable_fibu_plugin($value, $option){
	if(empty($value)){
		$value = array();
	}
	$disabled_posttypes = isset( $value['disabled_posttypes'] ) ? $value['disabled_posttypes']  : array();
	if(is_array($disabled_posttypes)){
		if(!in_array( 'product', $disabled_posttypes )){
			$value['disabled_posttypes'][] = 'product';
		};
	}
	return $value;
}

