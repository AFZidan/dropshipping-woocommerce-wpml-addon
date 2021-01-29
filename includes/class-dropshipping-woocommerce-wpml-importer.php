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
				add_action( 'wpml_import_translation_product',array($this,'get_product_formated'), 10, 3);
		}

	

		public function get_product_formated($product_ID,$single_product,$formated_data){
			if(!empty($product_ID)){
				
				global $sitepress,$wpdb,$woocommerce_wpml;

				$single_productData 	= array();
				$another_language   	= array();
				$current_lng_code   	= ICL_LANGUAGE_CODE;
				$language_info			= icl_get_languages();
				$language_count			= count($language_info);
				$active_language    	= $language_info[$current_lng_code];
				$language_details  	  	= apply_filters( 'wpml_post_language_details', NULL, $product_ID ) ;
				
				$active_language_code 	= $language_details['language_code'];

				unset($language_info[$active_language_code]);
				
				$categories 			= $single_product->categories;
				$attributes 			= $single_product->attributes;
				$wc_product_attr 		= $formated_data['raw_attributes'];
				$main_title				= $single_product->name->$active_language_code;
				$tids 					= $this->get_translation_id($sitepress,$product_ID);

				
			
				if(!empty($language_info)):
					foreach($language_info as $lang_key => $lang_info):
						$_POST['data']								= '';
						$single_productData['ID']   	  			= $product_ID;
						$single_productData['sku']   	  			= $single_product->sku;
						$single_productData[md5('title')] 			= $single_product->name->$lang_key;
						$single_productData['title'] 				= $single_product->name->$lang_key;
						
						$single_productData[md5('slug')]  			= sanitize_title($single_product->name->$lang_key);
						$single_productData[md5('product_excerpt')] = '';

						$single_productData[md5('post_content')] 	= $single_product->description->$lang_key;
						$single_productData['name'] 	  			= $single_product->name->$lang_key;
					
						$jobID										= $this->get_job_id($lang_key,$tids);

						$job_details['job_id']			  			= $product_ID;
						$job_details['target']			  			= $lang_key;
						$job_details['job_type']			  		= 'post_product';

						$categories_data 							= $this->product_taxonomy_data($categories,$active_language_code,$lang_key);
						$attributes_data 							= $this->product_attributes_data($product_ID,$attributes,$active_language_code,$lang_key);
						$post_data_string							= $this->post_data_string($single_product,$product_ID,$jobID,$attributes_data,$categories_data,$active_language_code,$lang_key);

					
						$_POST['data'] 								= $post_data_string;

						$save_product	= new WCML_Editor_UI_Product_Job($job_details, $woocommerce_wpml, $sitepress, $wpdb);
						$save_product->save_translations($single_productData);

					endforeach;
				endif;
			}
		}

		/**
		 * Get category from the API for translation 
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
		 * Get attribute from the API for translation 
		 */
		public function product_attributes_data($product_ID,$attributes_list,$active_language_code,$lang_key){
			$variation_string				= '';
			$attribute_string				= '';
			global $product;
		
			if(!empty($attributes_list)){
				foreach($attributes_list as $attributes){

					$att_name 				= sanitize_title($attributes->name->$active_language_code);
					$att_name_ 				= sanitize_title($attributes->name->$active_language_code).'_name';
				
					$att_trans_name 		= $attributes->name->$lang_key;
					$attr_options			= $attributes->options;
					
					$attribute_string 		.= "fields[$att_name_][data]=".$att_trans_name;
					$attribute_string 		.= "&fields[$att_name_][tid]=0";
					$attribute_string 		.= "&fields[$att_name_][format]=base64&";

					$attribute_variation 	= wc_get_product_terms($product_ID,'pa_'.$att_name,array('fields' => 'all'));
					
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
		 * Get job id from the product title.
		 * 
		 * @param string $lang_key product language key
		 * @return int
		 * @since 1.0.0
		 */

		/**
		 * Get job id from the product title
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
		 * Create post data string to pass
		 */
		public function post_data_string($single_product,$product_ID,$jobID,$attributes_data,$categories_data,$active_language_code,$lang_key){
			
			$pro_title											= $single_product->name->$lang_key;
			$pro_desc											= $single_product->description->$lang_key;
			$pro_slug											= sanitize_title($single_product->name->$lang_key);
			$title												= 'fields[title][data]='.$pro_title.'&fields[title][tid]=0&fields[title][format]=base64&';
			$slug												= 'fields[slug][data]='.$pro_slug.'&fields[slug][tid]=0&fields[slug][format]=base64&';
			$product_content									= 'product_content_original=&fields[product_content][data]='.$pro_desc.'&fields[product_content][tid]=0&fields[product_content][format]=base64&';
			$product_excerpt									= 'product_excerpt_original=&fields[product_excerpt][data]=&fields[product_excerpt][tid]=0&fields[product_excerpt][format]=base64&';
			$purchase_note										= 'fields[_purchase_note][data]=&fields[_purchase_note][tid]=0&fields[_purchase_note][format]=base64&';

			$starting_string 									= $title.$slug.$product_content.$product_excerpt.$purchase_note;
			$postData											= 'job_post_type=post_product&job_post_id='.$product_ID.'&job_id='.$jobID.'&source_lang='.$active_language_code.'&target_lang='.$lang_key.'&'.$starting_string.'&'.$attributes_data.$categories_data;
			$postData											= mb_substr($postData, 0, -1);
			
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
