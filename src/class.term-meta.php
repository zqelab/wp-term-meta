<?php

namespace Zqe;

/**
 *
 * @link       https://github.com/zqe
 * @since      1.0.0
 *
 * @package    Variable_Product_Swatches
 * @subpackage Variable_Product_Swatches/includes
 */
class Wp_Term_Meta
{
    /**
     *
     * @since    1.0.0
     */
    private $taxonomy;

    /**
     *
     * @since    1.0.0
     */
    private $fields = [];

    /**
     *
     * @since    1.0.0
     */
    public function __construct($taxonomy, $fields = [])
    {

        $this->taxonomy = $taxonomy;
        $this->fields = $fields;

        add_action('init', [$this, 'register']);
        add_action($this->taxonomy . '_add_form_fields', [$this, 'add_form_fields']);
        add_action($this->taxonomy . '_edit_form_fields', [$this, 'edit_form_fields']);
        add_action('create_' . $this->taxonomy, [$this, 'save']);
        add_action('edit_' . $this->taxonomy, [$this, 'save']);
        add_action('delete_' . $this->taxonomy, [$this, 'delete_term'], 5, 4);
        //add_filter( 'manage_edit-' . $this->taxonomy . '_columns', [ $this, 'edit_term_columns' ], 100, 3 );
        //add_filter( 'manage_' . $this->taxonomy . '_custom_column', [ $this, 'manage_term_custom_column' ], 100, 3 );

    }

    /**
     *
     * @since    1.0.0
     */
    public function register()
    {
        foreach ($this->fields as $key => $field) {
            register_meta('term', $field['id'], [$this, 'sanitize']);
        }
    }

    /**
     *
     * @since    1.0.0
     */
    public function add_form_fields()
    {
        $this->generate_add_fields();
    }

    /**
     *
     * @since    1.0.0
     */
    public function edit_form_fields($term)
    {
        $this->generate_edit_fields($term);
    }

    /**
     *
     * @since    1.0.0
     */
    public function generate_add_fields($term = false)
    {
        if (empty($this->fields)) {
            return;
        }
        foreach ($this->fields as $key => $field) {
            $field['value'] = $this->get($term, $field);
            ob_start();
            wp_nonce_field(basename(__FILE__), 'term_meta_text_nonce');
?>
            <div class="form-field <?php echo esc_attr($field['id']) ?> <?php echo empty($field['required']) ? '' : 'form-required' ?>">
                <?php if (!($field['type'] == 'checkbox' || $field['type'] == 'checkbox')) { ?>
                    <label for="<?php echo esc_attr($field['id']) ?>"><?php echo esc_html($field['label']); ?></label>
                <?php
                } else { ?>
                    <?php echo esc_html($field['label']); ?>
                <?php
                }
                echo ob_get_clean();
                $this->fields($field);
                ob_start();
                ?>
                <p><?php echo esc_html($field['desc']); ?></p>
            </div>
        <?php
            echo ob_get_clean();
        }
    }

    /**
     *
     * @since    1.0.0
     */
    public function generate_edit_fields($term = false)
    {
        if (empty($this->fields)) {
            return;
        }
        foreach ($this->fields as $key => $field) {
            $field['value'] = $this->get($term, $field);
            ob_start();
            wp_nonce_field(basename(__FILE__), 'term_meta_text_nonce');
        ?>
            <tr class="form-field  <?php echo esc_attr($field['id']) ?> <?php echo empty($field['required']) ? '' : 'form-required' ?>">
                <th scope="row">
                    <?php if (!($field['type'] == 'checkbox' || $field['type'] == 'checkbox')) { ?>
                        <label for="<?php echo esc_attr($field['id']) ?>"><?php echo esc_html($field['label']); ?></label>
                    <?php
                    } else { ?>
                        <?php echo esc_html($field['label']); ?>
                    <?php
                    } ?>
                </th>
                <td>
                    <?php
                    echo ob_get_clean();
                    $this->fields($field);
                    ob_start();
                    ?>
                    <p class="description"><?php echo esc_html($field['desc']); ?></p>
                </td>
            </tr>
        <?php
            echo ob_get_clean();
        }
    }

    /**
     *
     * @since    1.0.0
     */
    public function sanitize($type, $value)
    {
        switch ($type) {
            case 'color':
                return sanitize_text_field($value);
                break;
            case 'image':
                return sanitize_text_field($value);
                break;
            default:
                break;
        }
        return sanitize_text_field($value);
    }

    /**
     *
     * @since    1.0.0
     */
    public function save($term_id)
    {
        if (!isset($_POST['term_meta_text_nonce']) || !wp_verify_nonce($_POST['term_meta_text_nonce'], basename(__FILE__))) {
            return;
        }
        foreach ($this->fields as $field) {
            foreach ($_POST as $key => $value) {
                if ($field['id'] == $key) {
                    $value = $this->sanitize($field['type'], $value);
                    update_term_meta($term_id, $field['id'], $value);
                }
            }
        }
    }

    /**
     *
     * @since    1.0.0
     */
    public function delete_term($term_id, $tt_id, $taxonomy, $deleted_term)
    {

        global $wpdb;

        $term_id = absint($term_id);

        if ($term_id and $taxonomy == $this->taxonomy) {
            $wpdb->delete($wpdb->termmeta, array(
                'term_id' => $term_id
            ), array(
                '%d'
            ));
        }
    }

    /**
     *
     * @since    1.0.0
     */
    public function get($term, $field)
    {
        $value = isset($field['default']) ? $field['default'] : '';
        if (is_object($term)) {
            $value = get_term_meta($term->term_id, $field['id'], true);
        }
        return $value;
    }

    /**
     *
     * @since    1.0.0
     */
    private static function get_img_src($thumbnail_id = false)
    {
        if (!empty($thumbnail_id)) {
            $image = wp_get_attachment_thumb_url($thumbnail_id);
        } else {
            $image = self::placeholder_img_src();
        }
        return $image;
    }

    /**
     *
     * @since    1.0.0
     */
    private static function placeholder_img_src()
    {
        return function_exists('wc_placeholder_img_src') ? wc_placeholder_img_src() : null;
    }

    /**
     *
     * @since    1.0.0
     */
    public function fields($field)
    {
        switch ($field['type']) {
            case 'text':
            case 'url':
                ob_start();
                $this->text_url($field);
                echo ob_get_clean();
                break;
            case 'color':
                $this->field_color($field);
                break;
            case 'select':
            case 'select2':
                ob_start();
                $this->field_select_select2($field);
                echo ob_get_clean();
                break;
            case 'image':
                ob_start();
                $this->field_image($field);
                echo ob_get_clean();
                break;
            default:
                break;
        }
    }

    /**
     *
     * @since    1.0.0
     */
    public function field_image($field)
    {
        ?>
        <div class="variable-product-swatches-image-field-wrapper">
            <input class="attachment-id" type="hidden" name="<?php echo esc_attr($field['id']) ?>" value="<?php echo esc_attr($field['value']) ?>" />
            <div class="image-preview">
                <img src="<?php echo esc_url(self::get_img_src($field['value'])); ?>" />
            </div>
            <div class="button-wrapper">
                <button type="button" class="upload-image-button button button-primary button-small">
                    <?php esc_html_e('Upload', 'variable-product-swatches'); ?>
                </button>
                <button style="<?php echo (empty($field['value']) ? 'display:none' : '') ?>" type="button" class="remove-image-button button button-danger button-small">
                    <?php esc_html_e('Remove', 'variable-product-swatches'); ?>
                </button>
            </div>
        </div>
    <?php
    }
    /**
     *
     * @since    1.0.0
     */
    public function field_select_select2($field)
    {
        $field['options'] = isset($field['options']) ? $field['options'] : array();
        $field['multiple'] = isset($field['multiple']) ? ' multiple="multiple"' : '';
        $css_class = ($field['type'] == 'select2') ? 'variable-product-swatches-selectwoo' : '';
    ?>
        <select name="<?php echo $field['id'] ?>" id="<?php echo $field['id'] ?>" class="<?php echo $css_class ?>" <?php echo $field['multiple'] ?>>
            <?php
            foreach ($field['options'] as $key => $option) {
                echo '<option' . selected($field['value'], $key, false) . ' value="' . $key . '">' . $option . '</option>';
            }
            ?>
        </select>
    <?php
    }

    /**
     *
     * @since    1.0.0
     */
    public function field_color($field)
    {
        echo sprintf('<input type="text" class="zqe-color-picker wp-color-picker" name="%1$s" value="%2$s" />', $field['id'], $field['value']);
    }

    /**
     *
     * @since    1.0.0
     */
    public function text_url($field)
    {
    ?>
        <input name="<?php echo $field['id'] ?>" id="<?php echo $field['id'] ?>" type="<?php echo $field['type'] ?>" value="<?php echo $field['value'] ?>">
<?php
    }
}
