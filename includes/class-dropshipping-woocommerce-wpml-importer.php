<?php
/**
 * Knawat MP Importer class.
 *
 * @link       http://knawat.com/
 * @since      1.0.0
 * @category   Class
 * @author     Suraj Rathod
 *
 * @package    dropshipping_woocommerce_wpml_addon
 * @subpackage dropshipping_woocommerce_wpml_addon/includes
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
	 * Knawat_Dropshipping_wpml_Woocommerce_Importer Class.
	 */
	class Knawat_Dropshipping_wpml_Woocommerce_Importer extends WCML_Editor_UI_Product_Job{

		
		/**
		 * __construct function.
		 *
		 * @access public
		 * @return void
		 */
		public function __construct( ) {
			add_action( 'wpml_import_translation_product',array($this,'get_product_formated'), 10, 2);
		}

	
		/**
		 * Convert product translation data
		*/
		public function get_product_formated($product_ID,$single_product){
			if(!empty($product_ID)){
				
				global $sitepress,$wpdb,$woocommerce_wpml;

				$single_productData = array();
				$language_info		= icl_get_languages();
				$language_details	= apply_filters( 'wpml_post_language_details', NULL, $product_ID ) ;
				$active_language_code	= $sitepress->get_default_language();
				unset($language_info[$active_language_code]);
				
				$categories			= $single_product->categories;
				$attributes			= $single_product->attributes;
				$tids				= $this->get_translation_id($sitepress,$product_ID);
				$import_prd_lang 	= array_keys((array)$single_product->name);

				if(!empty($language_info)):
					foreach($language_info as $lang_key => $lang_info):
						if(in_array($lang_key,$import_prd_lang)){

							$post_title 								= mb_convert_encoding($single_product->name->$lang_key,"HTML-ENTITIES","UTF-8");
							$single_productData[md5('title')]			= sanitize_text_field($post_title);
							$single_productData[md5('post_title')]		= sanitize_text_field($post_title);
							$single_productData[md5('slug')]			= sanitize_title($post_title);
							$single_productData[md5('post_name')]		= sanitize_text_field($post_title);
							$single_productData[md5('post_content')]	= $single_product->description->$lang_key;
							$single_productData[md5('product_excerpt')] = '';

							$jobID										= $this->get_job_id($lang_key,$tids);
							$job_details['job_id']						= $product_ID;
							$job_details['target']						= $lang_key;
							$job_details['job_type']					= 'post_product';
							
							$categories_data 					= $this->product_taxonomy_data($categories,$active_language_code,$lang_key);
							$attributes_data 					= $this->product_attributes_data($product_ID,$attributes,$active_language_code,$lang_key);
							$post_data_string					= $this->post_data_string($single_product,$product_ID,$jobID,$attributes_data,$categories_data,$active_language_code,$lang_key);

							$_POST['data']							= $post_data_string;

							$save_product							= new WCML_Editor_UI_Product_Job($job_details, $woocommerce_wpml, $sitepress, $wpdb);
							$save_product->save_translations($single_productData);
						}
					endforeach;
				endif;
			}
		}

		/**
		 * Create product category/taxonomy string
		 */
		public function product_taxonomy_data($categories,$active_language_code,$lang_key){
			if(!empty($categories)){
				$taxonomy_string				= '';
				foreach($categories as $category){
					
						$category_name 			= $category->name->$active_language_code;
						$category_trans_name 	= $category->name->$lang_key;
						$category_treeNodeLevel = $category->treeNodeLevel;
						$term_data 				= get_term_by('name',$category_name,'product_cat');
						
						if(!empty($term_data)){
								$term_id = $term_data->term_id;
								// taxonomy string data as per save function 
								$taxonomy_string .= "fields[t_$term_id][data]=".$category_trans_name;
								$taxonomy_string .= "&fields[t_$term_id][tid]=0";
								$taxonomy_string .= "&fields[t_$term_id][format]=base64&";
						}
				}
				
			}
			return $taxonomy_string;
		}

		
		
		/**
		 * Create product attribute/variation string.
		 */
		public function product_attributes_data($product_ID,$attributes_list,$active_language_code,$lang_key){
			$variation_string				= '';
			$attribute_string				= '';
			global $product;
		
			if(!empty($attributes_list)){
				foreach($attributes_list as $attributes){

					$att_name 				= sanitize_title($attributes->name->$active_language_code);
					$att_format_name		= sanitize_title($attributes->name->$active_language_code).'_name';
					$att_trans_name 		= $attributes->name->$lang_key;
					$attr_options			= $attributes->options;
					
					$attribute_string 		.= "fields[$att_format_name][data]=".$att_trans_name;
					$attribute_string 		.= "&fields[$att_format_name][tid]=0";
					$attribute_string 		.= "&fields[$att_format_name][format]=base64&";

					
					if(!empty($attr_options)){
						foreach($attr_options as $key => $variation){

								$att_tran_value 		  = $variation->$lang_key;
								$att_orig_value 		  = $variation->$active_language_code;
								$att_var_data 			  = get_term_by('name',$att_orig_value,'pa_'.$att_name);
								
								$attribute_string .= "fields[$att_name][data]=".$att_tran_value;
								$attribute_string .= "&fields[$att_name][tid]=0";
								$attribute_string .= "&fields[$att_name][format]=base64&";
						}
					}	

					if(!empty($attr_options)){
						foreach($attr_options as $key => $variation){

								$att_tran_value 		  = $variation->$lang_key;
								$att_orig_value 		  = $variation->$active_language_code;
								$var_data 				  = get_term_by('name',$att_orig_value,'pa_'.$att_name);
							
								if(!empty($var_data)){
									$var_id 		   = $var_data->term_id;
									$variation_string .= "fields[t_$var_id][data]=".$att_tran_value;
									$variation_string .= "&fields[t_$var_id][tid]=0";
									$variation_string .= "&fields[t_$var_id][format]=base64&";
								}
						}
					}
				}
			}

			
			return $attribute_string.$variation_string;
		}

		/**
		 * Get the latest job id.
		 * 
		 * @param string $lang_key product language code
		 * @param string product translation id
		 * @return int
		 * @since 1.0.0
		 */
		public function get_job_id($lang_key,$tids){
			if(!empty($lang_key)){
				global $wpdb;
				$product_job_id = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT MAX(tj.job_id) FROM {$wpdb->prefix}icl_translate_job AS tj
									 LEFT JOIN {$wpdb->prefix}icl_translation_status AS ts
									 ON tj.rid = ts.rid WHERE ts.translation_id=%d",
						$tids[ $lang_key ]
					)
				);
				$job_id		= $product_job_id;
			}
			return $job_id;
		}


		/**
		 * Create post data string from all the data
		 */
		public function post_data_string($single_product,$product_ID,$jobID,$attributes_data,$categories_data,$active_language_code,$lang_key){
			
			$pro_title			= $single_product->name->$lang_key;
			$pro_desc			= $single_product->description->$lang_key;
			$pro_slug			= sanitize_title($single_product->name->$lang_key);
			$title				= 'fields[title][data]='.$pro_title.'&fields[title][tid]=0&fields[title][format]=base64&';
			$slug				= 'fields[slug][data]='.$pro_slug.'&fields[slug][tid]=0&fields[slug][format]=base64&';
			$product_content	= 'product_content_original=&fields[product_content][data]='.$pro_desc.'&fields[product_content][tid]=0&fields[product_content][format]=base64&';
			$product_excerpt	= 'product_excerpt_original=&fields[product_excerpt][data]=&fields[product_excerpt][tid]=0&fields[product_excerpt][format]=base64&';
			$purchase_note		= 'fields[_purchase_note][data]=&fields[_purchase_note][tid]=0&fields[_purchase_note][format]=base64&';

			$starting_string	= $title.$slug.$product_content.$product_excerpt.$purchase_note;
			$postData			= 'job_post_type=post_product&job_post_id='.$product_ID.'&job_id='.$jobID.'&source_lang='.$active_language_code.'&target_lang='.$lang_key.'&'.$starting_string.'&'.$attributes_data.$categories_data;
			$postData			= mb_substr($postData, 0, -1);
			
			return $postData;
		}


		/**
		 * Get translation id from Product id 
		 */
		public function get_translation_id($sitepress,$product_ID){
				$translated_ids = array();
				if(!isset($sitepress)) return;
			
				$trid 			= $sitepress->get_element_trid($product_ID, 'post_product');
				$translations 	= $sitepress->get_element_translations($trid, 'product');

				if(!empty($translations)){
					foreach( $translations as $lang =>$translation){
						$translated_ids[$translation->language_code] = $translation->translation_id;
						
					}
				}

			return $translated_ids;
		}

	}

endif;
