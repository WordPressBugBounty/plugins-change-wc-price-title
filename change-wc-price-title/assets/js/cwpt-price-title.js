/**
 * Shows "unit price x quantity = total" next to the quantity field
 * on the single product page.
 */
jQuery( function ( $ ) {
	var params = window.cwpt_settings_params,
		$form  = $( 'form.cart' ).first();

	if ( ! params || ! $form.length ) {
		return;
	}

	var unitPrice = 'simple' === params.product_type ? parseFloat( params.product_price ) : NaN,
		decimals  = parseInt( params.decimals, 10 ) || 0,
		$total    = $( '<p class="cwpt-quantity-total" aria-live="polite"></p>' ).hide();

	// Keep the total inside this product's form so other prices on the page are untouched.
	var $qtyWrap = $form.find( '.quantity' ).first();
	if ( $qtyWrap.length ) {
		$qtyWrap.before( $total );
	} else {
		$form.prepend( $total );
	}

	function formatPrice( amount ) {
		var parts  = amount.toFixed( decimals ).split( '.' ),
			symbol = params.currency_symbol;

		parts[0] = parts[0].replace( /\B(?=(\d{3})+(?!\d))/g, params.thousand_sep );

		var number = parts.join( params.decimal_sep );

		switch ( params.currency_pos ) {
			case 'right':
				return number + symbol;
			case 'left_space':
				return symbol + ' ' + number;
			case 'right_space':
				return number + ' ' + symbol;
			default:
				return symbol + number;
		}
	}

	function updateTotal() {
		var qty = parseFloat( $form.find( '[name=quantity]' ).val() );

		if ( isNaN( unitPrice ) || isNaN( qty ) || qty < 1 ) {
			$total.hide();
			return;
		}

		$total.text(
			params.total_format
				.replace( '%1$s', formatPrice( unitPrice ) )
				.replace( '%2$s', qty )
				.replace( '%3$s', formatPrice( unitPrice * qty ) )
		).show();
	}

	$form.on( 'input change', '[name=quantity]', updateTotal );

	// Variable products: use the price of the currently selected variation.
	$form.on( 'found_variation', function ( event, variation ) {
		unitPrice = parseFloat( variation.display_price );
		updateTotal();
	} );

	$form.on( 'reset_data hide_variation', function () {
		unitPrice = NaN;
		updateTotal();
	} );
} );
