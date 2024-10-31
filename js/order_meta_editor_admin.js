jQuery( document ).ready(function( $ ) {

// for the order item meta update page
        $('input.update_meta_button').click(function() {
                var meta_id=$(this).data('meta_id');
                var meta_name=$(this).data('meta_name');
                var meta_val=$('input#meta_id_'+meta_id).val();
                $('input#update_meta_item').val('1');
                $('input#meta_value').val(meta_val);
                $('input#meta_item').val(meta_id);
                $('input#meta_name').val(meta_name);
                $('form#order_item_edit').submit();
        });

}); // ready

