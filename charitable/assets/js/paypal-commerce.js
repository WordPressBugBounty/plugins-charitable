/**
 * Charitable PayPal Commerce Gateway
 *
 * Handles PayPal JS SDK integration for donation forms.
 * Supports PayPal Buttons and Card Fields (ACDC).
 *
 * @package Charitable/Gateways/PayPal Commerce
 * @since   1.9.0
 */

( function( $ ) {
	'use strict';

	// Diagnostic logger. Silent unless charitable_is_debug( 'paypal' ) is true on the PHP side.
	function debugLog() {
		if ( typeof CHARITABLE_PAYPAL_COMMERCE === 'undefined' || ! CHARITABLE_PAYPAL_COMMERCE.debug ) {
			return;
		}
		console.log.apply( console, arguments );
	}

	var CharitablePayPalCommerce = {

		/**
		 * Configuration from localized script.
		 */
		config: {},

		/**
		 * Current donation form.
		 */
		$form: null,

		/**
		 * PayPal buttons instance.
		 */
		buttonsInstance: null,

		/**
		 * Card Fields instance.
		 */
		cardFieldsInstance: null,

		/**
		 * Apple Pay instance.
		 */
		applePayInstance: null,

		/**
		 * Google Pay instance.
		 */
		googlePayInstance: null,

		/**
		 * Fastlane instance.
		 */
		fastlaneInstance: null,

		/**
		 * Fastlane payment component instance.
		 */
		fastlanePaymentComponent: null,

		/**
		 * Client token for Fastlane.
		 */
		clientToken: null,

		/**
		 * Whether buttons are rendered.
		 */
		buttonsRendered: false,

		/**
		 * Whether card fields are rendered.
		 */
		cardFieldsRendered: false,

		/**
		 * Whether Apple Pay is rendered.
		 */
		applePayRendered: false,

		/**
		 * Whether Google Pay is rendered.
		 */
		googlePayRendered: false,

		/**
		 * Whether Fastlane is rendered.
		 */
		fastlaneRendered: false,

		/**
		 * Currently selected payment method ('paypal' or 'card').
		 */
		selectedPaymentMethod: 'paypal',

		/**
		 * Initialize.
		 */
		init: function() {
			if ( typeof CHARITABLE_PAYPAL_COMMERCE === 'undefined' ) {
				console.error( 'Charitable PayPal Commerce: Config not found.' );
				return;
			}

			this.config = CHARITABLE_PAYPAL_COMMERCE;

			// Wait for PayPal SDK to be ready.
			if ( typeof paypal === 'undefined' ) {
				console.error( 'Charitable PayPal Commerce: PayPal SDK not loaded.' );
				return;
			}

			// Initialize on donation forms.
			this.initForms();

			// Listen for gateway changes.
			$( document ).on( 'change', 'input[name="gateway"]', this.onGatewayChange.bind( this ) );

			// Listen for payment method tab changes.
			$( document ).on( 'click', '.charitable-paypal-payment-method-tab', this.onPaymentMethodTabClick.bind( this ) );

			// Listen for form updates (e.g., after AJAX).
			$( document ).on( 'charitable:form:updated', this.onFormUpdated.bind( this ) );
		},

		/**
		 * Initialize PayPal on all donation forms.
		 */
		initForms: function() {
			var self = this;

			$( '.charitable-donation-form' ).each( function() {
				var $form = $( this );
				var $container = $form.find( '#charitable-paypal-commerce-container' );

				if ( $container.length && ! $container.data( 'initialized' ) ) {
					self.initForm( $form, $container );
				}
			} );
		},

		/**
		 * Initialize PayPal on a specific form.
		 *
		 * @param {jQuery} $form     Form element.
		 * @param {jQuery} $container PayPal container element.
		 */
		initForm: function( $form, $container ) {
			var self = this;
			this.$form = $form;

			// Check if PayPal Commerce is the selected gateway.
			// Match either a :checked radio (multi-gateway picker) or a hidden input
			// (single-gateway mode where Charitable emits the gateway as type=hidden).
			// Without the hidden-input branch, single-gateway legacy donation forms
			// never identify the active gateway and the SDK buttons never render.
			var $gw = $form.find( 'input[name="gateway"]:checked, input[name="gateway"][type="hidden"]' );
			var selectedGateway = $gw.length ? $gw.val() : '';

			if ( selectedGateway !== this.config.gateway_id ) {
				$container.hide();
				return;
			}

			$container.show();
			$container.data( 'initialized', true );

			// Hide the default submit button when PayPal is selected.
			this.toggleSubmitButton( false );

			// Build the payment UI.
			this.buildPaymentUI( $container );
		},

		/**
		 * Build the payment UI with tabs for PayPal buttons and Card Fields.
		 *
		 * @param {jQuery} $container Container element.
		 */
		buildPaymentUI: function( $container ) {
			var self = this;
			var hasCardFields = this.config.card_fields && this.config.card_fields.enabled;
			var hasApplePay = this.config.apple_pay && this.config.apple_pay.enabled;
			var hasGooglePay = this.config.google_pay && this.config.google_pay.enabled;
			var hasMultipleMethods = hasCardFields || hasApplePay || hasGooglePay;

			// Clear existing content.
			$container.empty();

			// Create the UI structure.
			var $wrapper = $( '<div class="charitable-paypal-payment-wrapper"></div>' );

			if ( hasMultipleMethods ) {
				// Create tabs for different payment methods.
				var tabsHtml = '<div class="charitable-paypal-payment-tabs">';

				// PayPal tab (always first).
				tabsHtml += '<button type="button" class="charitable-paypal-payment-method-tab active" data-method="paypal">' +
					'<span class="tab-icon paypal-icon"></span>' +
					'<span class="tab-label">PayPal</span>' +
				'</button>';

				// Card fields tab.
				if ( hasCardFields ) {
					tabsHtml += '<button type="button" class="charitable-paypal-payment-method-tab" data-method="card">' +
						'<span class="tab-icon card-icon"></span>' +
						'<span class="tab-label">' + ( this.config.i18n.pay_with_card || 'Pay with Card' ) + '</span>' +
					'</button>';
				}

				// Apple Pay tab.
				if ( hasApplePay ) {
					tabsHtml += '<button type="button" class="charitable-paypal-payment-method-tab" data-method="applepay">' +
						'<span class="tab-icon apple-pay-icon"></span>' +
						'<span class="tab-label">Apple Pay</span>' +
					'</button>';
				}

				// Google Pay tab.
				if ( hasGooglePay ) {
					tabsHtml += '<button type="button" class="charitable-paypal-payment-method-tab" data-method="googlepay">' +
						'<span class="tab-icon google-pay-icon"></span>' +
						'<span class="tab-label">Google Pay</span>' +
					'</button>';
				}

				tabsHtml += '</div>';
				var $tabs = $( tabsHtml );
				$wrapper.append( $tabs );
			}

			// PayPal buttons container.
			var $buttonsPane = $( '<div class="charitable-paypal-pane charitable-paypal-buttons-pane active" data-method="paypal"></div>' );
			$wrapper.append( $buttonsPane );

			if ( hasCardFields ) {
				// Card fields container.
				var $cardPane = $( '<div class="charitable-paypal-pane charitable-paypal-card-pane" data-method="card"></div>' );

				// Card fields form structure.
				var $cardForm = $(
					'<div class="charitable-paypal-card-form">' +
						'<div class="charitable-paypal-card-field-wrapper">' +
							'<label for="charitable-card-number">' + ( this.config.i18n.card_number || 'Card Number' ) + '</label>' +
							'<div id="charitable-card-number" class="charitable-paypal-card-field"></div>' +
						'</div>' +
						'<div class="charitable-paypal-card-row">' +
							'<div class="charitable-paypal-card-field-wrapper charitable-paypal-card-expiry">' +
								'<label for="charitable-card-expiry">' + ( this.config.i18n.expiry || 'Expiry Date' ) + '</label>' +
								'<div id="charitable-card-expiry" class="charitable-paypal-card-field"></div>' +
							'</div>' +
							'<div class="charitable-paypal-card-field-wrapper charitable-paypal-card-cvv">' +
								'<label for="charitable-card-cvv">' + ( this.config.i18n.cvv || 'CVV' ) + '</label>' +
								'<div id="charitable-card-cvv" class="charitable-paypal-card-field"></div>' +
							'</div>' +
						'</div>' +
						'<div class="charitable-paypal-card-field-wrapper">' +
							'<label for="charitable-card-name">' + ( this.config.i18n.card_name || 'Name on Card' ) + '</label>' +
							'<input type="text" id="charitable-card-name" class="charitable-paypal-card-name-input" placeholder="John Doe" />' +
						'</div>' +
						'<div class="charitable-paypal-card-submit-wrapper">' +
							'<button type="button" id="charitable-card-submit" class="charitable-paypal-card-submit button">' +
								'<span class="button-text">' + ( this.config.i18n.donate || 'Donate' ) + '</span>' +
							'</button>' +
						'</div>' +
						'<div class="charitable-paypal-card-errors"></div>' +
					'</div>'
				);

				$cardPane.append( $cardForm );
				$wrapper.append( $cardPane );

				// Add card submit handler.
				$cardForm.find( '#charitable-card-submit' ).on( 'click', function( e ) {
					e.preventDefault();
					self.submitCardPayment();
				} );
			}

			// Apple Pay container.
			if ( hasApplePay ) {
				var $applePayPane = $( '<div class="charitable-paypal-pane charitable-paypal-applepay-pane" data-method="applepay">' +
					'<div id="charitable-apple-pay-container" class="charitable-apple-pay-container"></div>' +
				'</div>' );
				$wrapper.append( $applePayPane );
			}

			// Google Pay container.
			if ( hasGooglePay ) {
				var $googlePayPane = $( '<div class="charitable-paypal-pane charitable-paypal-googlepay-pane" data-method="googlepay">' +
					'<div id="charitable-google-pay-container" class="charitable-google-pay-container"></div>' +
				'</div>' );
				$wrapper.append( $googlePayPane );
			}

			$container.append( $wrapper );

			// Render PayPal buttons.
			this.renderButtons( $buttonsPane );

			// Render Card Fields if available.
			if ( hasCardFields ) {
				this.renderCardFields();
			}

			// Render Apple Pay if available.
			if ( hasApplePay ) {
				this.renderApplePay();
			}

			// Render Google Pay if available.
			if ( hasGooglePay ) {
				this.renderGooglePay();
			}

			// Initialize Fastlane if available.
			if ( this.shouldShowFastlane() ) {
				this.initFastlane();
			}
		},

		/**
		 * Handle payment method tab click.
		 *
		 * @param {Event} e Click event.
		 */
		onPaymentMethodTabClick: function( e ) {
			e.preventDefault();
			var $tab = $( e.currentTarget );
			var method = $tab.data( 'method' );

			if ( method === this.selectedPaymentMethod ) {
				return;
			}

			this.selectedPaymentMethod = method;

			// Update tab states.
			$tab.siblings().removeClass( 'active' );
			$tab.addClass( 'active' );

			// Update pane visibility.
			var $container = $tab.closest( '#charitable-paypal-commerce-container' );
			$container.find( '.charitable-paypal-pane' ).removeClass( 'active' );
			$container.find( '.charitable-paypal-pane[data-method="' + method + '"]' ).addClass( 'active' );
		},

		/**
		 * Render PayPal buttons.
		 *
		 * @param {jQuery} $container Container element.
		 */
		renderButtons: function( $container ) {
			var self = this;

			// Wrap SDK buttons in a stable container.
			var $sdkWrapper = $( '<div class="charitable-paypal-sdk-buttons"></div>' );
			$container.append( $sdkWrapper );

			try {
				this.buttonsInstance = paypal.Buttons( {
					style: this.config.button_style,
					appSwitchWhenAvailable: true,

					/**
					 * Create order when button is clicked.
					 */
					createOrder: function( data, actions ) {
						return self.createOrder( data.fundingSource || 'paypal' );
					},

					/**
					 * Handle approval.
					 */
					onApprove: function( data, actions ) {
						return self.onApprove( data );
					},

					/**
					 * Handle cancellation.
					 */
					onCancel: function( data ) {
						self.onCancel( data );
					},

					/**
					 * Handle errors.
					 */
					onError: function( err ) {
						self.onError( err );
					},

					/**
					 * Called when button is clicked (before createOrder).
					 */
					onClick: function( data, actions ) {
						// Validate form before proceeding.
						if ( ! self.validateForm() ) {
							return actions.reject();
						}
						return actions.resolve();
					}
				} );

				this.buttonsInstance.render( $sdkWrapper[0] ).then( function() {
					self.buttonsRendered = true;
				} );

			} catch ( err ) {
				console.error( 'Charitable PayPal Commerce: Error rendering buttons:', err );
				// Build the notice via DOM nodes + .text() instead of concatenating into .html()
				// so the i18n.error string is rendered as text regardless of its contents.
				// Defense-in-depth: the source value is server-controlled via wp_localize_script,
				// but using text() removes the possibility of a future contributor making the
				// string user-influenced and breaking out of the surrounding tag.
				$sdkWrapper.empty().append(
					$( '<p>', { 'class': 'charitable-notice' } ).text( this.config.i18n.error )
				);
			}
		},

		/**
		 * Render Card Fields (ACDC).
		 */
		renderCardFields: function() {
			var self = this;

			// Check if Card Fields component is available.
			if ( typeof paypal.CardFields === 'undefined' ) {
				console.warn( 'Charitable PayPal Commerce: Card Fields component not available.' );
				return;
			}

			try {
				// Get card fields style from config.
				var style = this.config.card_fields.style || {};

				this.cardFieldsInstance = paypal.CardFields( {
					style: style,

					/**
					 * Create order for card payment.
					 */
					createOrder: function() {
						return self.createOrder( 'card' );
					},

					/**
					 * Handle approval after 3DS (if required).
					 */
					onApprove: function( data ) {
						return self.onApprove( data );
					},

					/**
					 * Handle errors.
					 */
					onError: function( err ) {
						self.onCardError( err );
					}
				} );

				// Check eligibility.
				if ( this.cardFieldsInstance.isEligible() ) {
					// Render individual card fields.
					this.cardFieldsInstance.NumberField().render( '#charitable-card-number' );
					this.cardFieldsInstance.ExpiryField().render( '#charitable-card-expiry' );
					this.cardFieldsInstance.CVVField().render( '#charitable-card-cvv' );

					this.cardFieldsRendered = true;
				} else {
					console.warn( 'Charitable PayPal Commerce: Card Fields not eligible for this merchant.' );
					// Hide the card tab if not eligible.
					this.$form.find( '.charitable-paypal-payment-method-tab[data-method="card"]' ).hide();
				}

			} catch ( err ) {
				console.error( 'Charitable PayPal Commerce: Error rendering card fields:', err );
				// Hide the card tab on error.
				this.$form.find( '.charitable-paypal-payment-method-tab[data-method="card"]' ).hide();
			}
		},

		/**
		 * Render Apple Pay button.
		 */
		renderApplePay: function() {
			var self = this;

			// Check if Apple Pay component is available.
			if ( typeof paypal.Applepay === 'undefined' ) {
				console.warn( 'Charitable PayPal Commerce: Apple Pay component not available.' );
				this.$form.find( '.charitable-paypal-payment-method-tab[data-method="applepay"]' ).hide();
				return;
			}

			// Check if Apple Pay is supported on this device.
			if ( typeof window.ApplePaySession === 'undefined' || ! window.ApplePaySession.canMakePayments() ) {
				console.warn( 'Charitable PayPal Commerce: Apple Pay not supported on this device.' );
				this.$form.find( '.charitable-paypal-payment-method-tab[data-method="applepay"]' ).hide();
				return;
			}

			try {
				this.applePayInstance = paypal.Applepay( {
					/**
					 * Create order for Apple Pay payment.
					 */
					createOrder: function() {
						return self.createOrder( 'applepay' );
					},

					/**
					 * Handle approval.
					 */
					onApprove: function( data ) {
						return self.onApprove( data );
					},

					/**
					 * Handle errors.
					 */
					onError: function( err ) {
						self.onError( err );
					},

					/**
					 * Validate form before proceeding.
					 */
					onClick: function( data, actions ) {
						if ( ! self.validateForm() ) {
							return actions.reject();
						}
						return actions.resolve();
					}
				} );

				// Try different eligibility checking approaches
				var isEligible = false;

				if ( typeof this.applePayInstance.isEligible === 'function' ) {
					// Standard approach
					isEligible = this.applePayInstance.isEligible();
				} else if ( typeof this.applePayInstance.canMakePayments === 'function' ) {
					// Alternative approach
					isEligible = this.applePayInstance.canMakePayments();
				} else {
					// Fallback - just try to render and see what happens
					isEligible = true;
				}

				// Apple Pay doesn't have a render method - create the button manually
				this.renderApplePayButton();
				this.applePayRendered = true;

				// Pre-fetch Apple Pay config so it's available synchronously on click.
				// ApplePaySession must be created from a synchronous user gesture handler.
				this.applePayInstance.config().then( function( cfg ) {
					self._applePayConfig = cfg;
				}).catch( function( err ) {
					console.warn( 'Charitable PayPal Commerce: Could not pre-fetch Apple Pay config:', err );
				});

			} catch ( err ) {
				console.error( 'Charitable PayPal Commerce: Error rendering Apple Pay:', err );
				this.$form.find( '.charitable-paypal-payment-method-tab[data-method="applepay"]' ).hide();
			}
		},

		/**
		 * Render Google Pay button.
		 */
		renderGooglePay: function() {
			var self = this;

			// Check if PayPal Google Pay component is available.
			if ( typeof paypal.Googlepay === 'undefined' ) {
				console.warn( 'Charitable PayPal Commerce: Google Pay component not available.' );
				this.$form.find( '.charitable-paypal-payment-method-tab[data-method="googlepay"]' ).hide();
				return;
			}

			// Check if Google Pay API is loaded.
			if ( typeof google === 'undefined' || typeof google.payments === 'undefined' ) {
				console.warn( 'Charitable PayPal Commerce: Google Pay API not available.' );
				this.$form.find( '.charitable-paypal-payment-method-tab[data-method="googlepay"]' ).hide();
				return;
			}

			try {
				this.googlePayInstance = paypal.Googlepay();

				var isSandbox  = this.config.google_pay && this.config.google_pay.sandbox;
				var $container = this.$form.find( '#charitable-google-pay-container' );

				// Fetch Google Pay config from PayPal SDK.
				this.googlePayInstance.config().then( function( googlePayConfig ) {

					var paymentsClient = new google.payments.api.PaymentsClient({
						environment: isSandbox ? 'TEST' : 'PRODUCTION'
					});

					return paymentsClient.isReadyToPay({
						apiVersion:        googlePayConfig.apiVersion,
						apiVersionMinor:   googlePayConfig.apiVersionMinor,
						allowedPaymentMethods: googlePayConfig.allowedPaymentMethods
					}).then( function( response ) {
						if ( ! response.result ) {
							console.warn( 'Charitable PayPal Commerce: Google Pay not ready to pay.' );
							self.$form.find( '.charitable-paypal-payment-method-tab[data-method="googlepay"]' ).hide();
							return;
						}

						// Render the official Google Pay button.
						var button = paymentsClient.createButton({
							onClick: function() {
								if ( ! self.validateForm() ) {
									return;
								}

								self.showProcessing();

								var amount = String( self.getDonationAmount() );

								self.createOrder( 'googlepay' ).then( function( orderID ) {
									// Build merchantInfo: ensure merchantName is present (required by Google Pay).
									// In TEST mode, merchantId must be omitted — passing a real ID causes OR_BIBED_06.
									var merchantInfo = Object.assign( {}, googlePayConfig.merchantInfo );
									if ( ! merchantInfo.merchantName && self.config.google_pay && self.config.google_pay.merchant_name ) {
										merchantInfo.merchantName = self.config.google_pay.merchant_name;
									}
									if ( isSandbox && merchantInfo.merchantId ) {
										delete merchantInfo.merchantId;
									}

									// Build the payment data request from PayPal's config.
									// Explicitly pick known-valid fields to avoid leaking internal PayPal properties
									// (e.g. isEligible) into the Google Pay API request.
									var paymentDataRequest = {
										apiVersion:            googlePayConfig.apiVersion,
										apiVersionMinor:       googlePayConfig.apiVersionMinor,
										allowedPaymentMethods: googlePayConfig.allowedPaymentMethods,
										merchantInfo:          merchantInfo,
										transactionInfo: {
											totalPriceStatus: 'FINAL',
											totalPrice:       amount,
											currencyCode:     self.config.currency || 'USD'
										}
									};
									return paymentsClient.loadPaymentData( paymentDataRequest ).then( function( paymentData ) {
										return self.googlePayInstance.confirmOrder({
											orderId:           orderID,
											paymentMethodData: paymentData.paymentMethodData
										}).then( function() {
											return self.onApprove({ orderID: orderID });
										});
									});

								}).catch( function( err ) {
									self.hideProcessing();
									if ( err && err.statusCode === 'CANCELED' ) {
										return; // User cancelled — no error needed.
									}
									self.onError( err );
								});
							}
						});

						$container.empty().append( button );
						self.googlePayRendered = true;
					});

				}).catch( function( err ) {
					console.error( 'Charitable PayPal Commerce: Error initialising Google Pay:', err );
					self.$form.find( '.charitable-paypal-payment-method-tab[data-method="googlepay"]' ).hide();
				});

			} catch ( err ) {
				console.error( 'Charitable PayPal Commerce: Error rendering Google Pay:', err );
				this.$form.find( '.charitable-paypal-payment-method-tab[data-method="googlepay"]' ).hide();
			}
		},

		/**
		 * Render Apple Pay button manually.
		 */
		renderApplePayButton: function() {
			var self = this;
			var $container = this.$form.find( '#charitable-apple-pay-container' );

			// Create Apple Pay button HTML
			var buttonHtml = '<button type="button" id="charitable-apple-pay-button" class="charitable-apple-pay-button">' +
				'<span class="apple-pay-logo"></span>' +
				'<span class="apple-pay-text">Pay</span>' +
			'</button>';

			$container.html( buttonHtml );

			// Add click handler
			$container.find( '#charitable-apple-pay-button' ).on( 'click', function( e ) {
				e.preventDefault();
				self.handleApplePayClick();
			} );
		},

		/**
		 * Handle Apple Pay button click.
		 */
		handleApplePayClick: function() {
			var self = this;

			if ( ! this.validateForm() ) {
				return;
			}

			if ( ! this.applePayInstance ) {
				this.showError( 'Apple Pay not available' );
				return;
			}

			if ( ! this._applePayConfig ) {
				this.showError( 'Apple Pay is not ready yet. Please try again.' );
				return;
			}

			// ApplePaySession MUST be created synchronously from a user gesture handler.
			// Use the pre-fetched config; createOrder runs inside onvalidatemerchant.
			var applePayConfig = this._applePayConfig;
			var paymentRequest = {
				countryCode:                  applePayConfig.countryCode,
				merchantCapabilities:         applePayConfig.merchantCapabilities,
				supportedNetworks:            applePayConfig.supportedNetworks,
				currencyCode:                 self.config.currency || 'USD',
				requiredBillingContactFields: [ 'postalAddress' ],
				total: {
					label:  'Donation',
					type:   'final',
					amount: String( self.getDonationAmount() )
				}
			};

			var session = new ApplePaySession( 4, paymentRequest );
			var orderID = null;

			this.showProcessing();

			session.onvalidatemerchant = function( event ) {
				// Create the order and validate merchant concurrently.
				Promise.all([
					self.createOrder( 'applepay' ),
					self.applePayInstance.validateMerchant({ validationUrl: event.validationURL })
				]).then( function( results ) {
					orderID = results[0];
					session.completeMerchantValidation( results[1].merchantSession );
				}).catch( function( err ) {
					console.error( 'Charitable PayPal Commerce: Apple Pay setup failed:', err );
					session.abort();
					self.hideProcessing();
					self.showError( self.config.i18n.error );
				});
			};

			session.onpaymentauthorized = function( event ) {
				debugLog( 'Charitable PayPal Commerce: onpaymentauthorized fired. orderID:', orderID );
				self.applePayInstance.confirmOrder({
					orderId:        orderID,
					token:          event.payment.token,
					billingContact: event.payment.billingContact
				}).then( function( confirmResult ) {
					debugLog( 'Charitable PayPal Commerce: confirmOrder success:', confirmResult );
					session.completePayment( ApplePaySession.STATUS_SUCCESS );
					self.onApprove({ orderID: orderID });
				}).catch( function( err ) {
					console.error( 'Charitable PayPal Commerce: confirmOrder failed:', err );
					session.completePayment( ApplePaySession.STATUS_FAILURE );
					self.hideProcessing();
					self.onError( err );
				});
			};

			session.oncancel = function() {
				self.hideProcessing();
			};

			self.hideProcessing();
			session.begin();
		},

		/**
		 * Initialize Fastlane.
		 */
		initFastlane: function() {
			var self = this;

			// Check if Fastlane should be shown.
			if ( ! this.shouldShowFastlane() ) {
				debugLog( 'Charitable PayPal Commerce: Fastlane not available for current user' );
				return;
			}

			// Check if Fastlane component is available.
			if ( typeof paypal.Fastlane === 'undefined' ) {
				console.warn( 'Charitable PayPal Commerce: Fastlane component not available.' );
				return;
			}

			// Get client token first.
			this.getClientToken().then( function( clientToken ) {
				if ( ! clientToken ) {
					console.error( 'Charitable PayPal Commerce: Failed to get client token for Fastlane' );
					return;
				}

				self.clientToken = clientToken;

				// Initialize Fastlane.
				return paypal.Fastlane( {
					clientToken: clientToken
				} );

			} ).then( function( fastlaneInstance ) {
				if ( ! fastlaneInstance ) {
					console.warn( 'Charitable PayPal Commerce: Fastlane instance is null, skipping.' );
					return;
				}

				self.fastlaneInstance = fastlaneInstance;
				debugLog( 'Charitable PayPal Commerce: Fastlane initialized successfully' );

				// Hide the PayPal tab to eliminate duplicate PayPal/Venmo/Pay Later buttons.
				// The Fastlane SDK renders express PayPal/Venmo buttons in #charitable-fastlane-container,
				// which already covers the PayPal wallet payment path.
				var $paypalTab = self.$form.find( '.charitable-paypal-payment-method-tab[data-method="paypal"]' );
				var $paypalPane = self.$form.find( '.charitable-paypal-pane[data-method="paypal"]' );
				$paypalTab.hide().removeClass( 'active' );
				$paypalPane.hide().removeClass( 'active' );

				// Activate the next available tab (card preferred, then Google Pay, Apple Pay)
				// using direct DOM manipulation to avoid triggering delegated events inside a Promise.
				var $nextTab = self.$form.find(
					'.charitable-paypal-payment-method-tab[data-method="card"],' +
					'.charitable-paypal-payment-method-tab[data-method="googlepay"],' +
					'.charitable-paypal-payment-method-tab[data-method="applepay"]'
				).first();

				if ( $nextTab.length ) {
					var nextMethod = $nextTab.data( 'method' );
					self.$form.find( '.charitable-paypal-payment-method-tab' ).removeClass( 'active' );
					$nextTab.addClass( 'active' );
					self.$form.find( '.charitable-paypal-pane' ).removeClass( 'active' );
					self.$form.find( '.charitable-paypal-pane[data-method="' + nextMethod + '"]' ).addClass( 'active' );
					self.selectedPaymentMethod = nextMethod;
				} else {
					// No other tabs — hide the tabs bar entirely.
					self.$form.find( '.charitable-paypal-payment-tabs' ).hide();
				}

				// Render Fastlane if we have an email.
				self.renderFastlane();

			} ).catch( function( err ) {
				console.error( 'Charitable PayPal Commerce: Error initializing Fastlane:', err );

				// Graceful degradation: Hide Fastlane tab and ensure other payment methods work.
				self.$form.find( '.charitable-paypal-payment-method-tab[data-method="fastlane"]' ).hide();

				// If Fastlane was the only payment method, ensure PayPal button is visible.
				var $paypalTab = self.$form.find( '.charitable-paypal-payment-method-tab[data-method="paypal"]' );
				if ( $paypalTab.length && ! $paypalTab.is( ':visible' ) ) {
					$paypalTab.show();
					self.onPaymentMethodTabClick( { currentTarget: $paypalTab[0], preventDefault: function() {} } );
				}

				debugLog( 'Charitable PayPal Commerce: Fastlane fallback to standard PayPal completed' );
			} );
		},

		/**
		 * Check if Fastlane should be shown for current user.
		 */
		shouldShowFastlane: function() {
			// Check if Fastlane is enabled in config.
			if ( ! this.config.fastlane || ! this.config.fastlane.enabled ) {
				return false;
			}

			var behavior = this.config.fastlane.behavior || 'guest_only';
			var isLoggedIn = this.config.is_logged_in || false;

			// Show for all users if configured.
			if ( 'all_users' === behavior ) {
				return true;
			}

			// Default behavior: guest users only.
			return ! isLoggedIn;
		},

		/**
		 * Get client token for Fastlane.
		 * Uses the pre-generated token from localized config when available so that
		 * it matches the data-user-id-token embedded on the SDK script tag.
		 * Falls back to AJAX if not pre-generated.
		 */
		getClientToken: function() {
			var preGenerated = CharitablePayPalCommerce.config.fastlane && CharitablePayPalCommerce.config.fastlane.client_token;
			if ( preGenerated ) {
				return Promise.resolve( preGenerated );
			}
			return new Promise( function( resolve, reject ) {
				$.ajax( {
					url: CharitablePayPalCommerce.config.ajax_url,
					type: 'POST',
					data: {
						action: 'charitable_paypal_commerce_get_client_token',
						nonce: CharitablePayPalCommerce.config.nonce
					},
					success: function( response ) {
						if ( response.success && response.data.client_token ) {
							resolve( response.data.client_token );
						} else {
							reject( new Error( response.data ? response.data.message : 'Failed to get client token' ) );
						}
					},
					error: function( xhr, status, error ) {
						reject( new Error( 'AJAX error: ' + error ) );
					}
				} );
			} );
		},

		/**
		 * Render Fastlane.
		 */
		renderFastlane: function() {
			var self = this;

			var $container = this.$form.find( '#charitable-fastlane-container' );

			if ( $container.length === 0 ) {
				console.warn( 'Charitable PayPal Commerce: Fastlane container not found' );
				return;
			}

			// Build Fastlane UI with PayPal button above email (IWT items 1, 3, 4).
			var fastlaneHtml =
				'<div id="charitable-fastlane-paypal-button-container"></div>' +
				'<div class="charitable-fastlane-or-divider"><span>' + ( this.config.i18n.or || 'or' ) + '</span></div>' +
				'<div class="charitable-fastlane-wrapper">' +
					'<div class="charitable-fastlane-email-section">' +
						'<div class="charitable-fastlane-email-label-row">' +
							'<label for="charitable-fastlane-email">' + this.config.i18n.email_address + '</label>' +
							'<div id="charitable-fastlane-watermark"></div>' +
						'</div>' +
						'<input type="email" id="charitable-fastlane-email" name="fastlane_email" placeholder="' + this.config.i18n.email_placeholder + '">' +
						'<button type="button" id="charitable-fastlane-lookup" class="charitable-fastlane-btn">' + this.config.i18n.continue + '</button>' +
					'</div>' +
					'<div class="charitable-fastlane-payment-section" style="display:none;">' +
						'<div id="charitable-fastlane-payment-component"></div>' +
						'<button type="button" id="charitable-fastlane-pay-button" class="charitable-fastlane-pay-btn" style="display:none;">' + this.config.i18n.complete_payment + '</button>' +
					'</div>' +
				'</div>';

			$container.html( fastlaneHtml );

			// Render PayPal button above email field (IWT items 3 & 4).
			if ( typeof paypal !== 'undefined' && paypal.Buttons ) {
				var fastlanePaypalButtons = paypal.Buttons( {
					style: this.config.button_style,
					createOrder: function() {
						return self.createOrder( 'paypal' );
					},
					onApprove: function( data ) {
						return self.onApprove( data );
					},
					onError: function( err ) {
						self.onError( err );
					},
					onCancel: function() {
						self.onCancel();
					}
				} );

				if ( fastlanePaypalButtons.isEligible() ) {
					fastlanePaypalButtons.render( '#charitable-fastlane-paypal-button-container' );
				}
			}

			// Render Fastlane watermark component (IWT item 1).
			if ( this.fastlaneInstance && typeof this.fastlaneInstance.FastlaneWatermarkComponent === 'function' ) {
				this.fastlaneInstance.FastlaneWatermarkComponent( {
					includeAdditionalInfo: true
				} ).then( function( watermarkComponent ) {
					watermarkComponent.render( '#charitable-fastlane-watermark' );
				} ).catch( function( err ) {
					console.warn( 'Charitable PayPal Commerce: Fastlane watermark render failed:', err );
				} );
			}

			// Bind events.
			$container.find( '#charitable-fastlane-lookup' ).on( 'click', function() {
				self.handleFastlaneEmailLookup();
			} );

			$container.find( '#charitable-fastlane-email' ).on( 'keypress', function( e ) {
				if ( e.which === 13 ) { // Enter key
					self.handleFastlaneEmailLookup();
				}
			} );

			this.fastlaneRendered = true;
			debugLog( 'Charitable PayPal Commerce: Fastlane UI rendered' );
		},

		/**
		 * Handle Fastlane email lookup.
		 */
		handleFastlaneEmailLookup: function() {
			var self = this;
			var email = this.$form.find( '#charitable-fastlane-email' ).val().trim();

			if ( ! email || ! this.isValidEmail( email ) ) {
				this.showError( this.config.i18n.invalid_email );
				return;
			}

			// Show loading state.
			var $button = this.$form.find( '#charitable-fastlane-lookup' );
			var originalText = $button.text();
			$button.text( this.config.i18n.loading ).prop( 'disabled', true );

			// Use Fastlane identity lookup.
			if ( this.fastlaneInstance && typeof this.fastlaneInstance.identity === 'object' &&
				 typeof this.fastlaneInstance.identity.lookupCustomerByEmail === 'function' ) {

				this.fastlaneInstance.identity.lookupCustomerByEmail( email ).then( function( result ) {
					if ( result && result.customerContextId ) {
						// Customer found - trigger member authentication.
						debugLog( 'Charitable PayPal Commerce: Fastlane customer found, triggering authentication' );
						self.handleFastlaneMemberAuthentication( result.customerContextId );
					} else {
						// New customer - proceed with guest flow.
						debugLog( 'Charitable PayPal Commerce: New Fastlane customer, proceeding with guest flow' );
						self.handleFastlaneGuestFlow( email );
					}
				} ).catch( function( err ) {
					console.error( 'Charitable PayPal Commerce: Fastlane lookup error:', err );
					// Fallback to guest flow.
					self.handleFastlaneGuestFlow( email );
				} ).finally( function() {
					// Restore button.
					$button.text( originalText ).prop( 'disabled', false );
				} );

			} else {
				// Fallback: proceed with guest flow.
				debugLog( 'Charitable PayPal Commerce: Fastlane identity lookup not available, using guest flow' );
				this.handleFastlaneGuestFlow( email );
				$button.text( originalText ).prop( 'disabled', false );
			}
		},

		/**
		 * Handle Fastlane member authentication.
		 */
		handleFastlaneMemberAuthentication: function( customerContextId ) {
			var self = this;

			if ( this.fastlaneInstance && typeof this.fastlaneInstance.identity === 'object' &&
				 typeof this.fastlaneInstance.identity.triggerAuthenticationFlow === 'function' ) {

				this.fastlaneInstance.identity.triggerAuthenticationFlow( customerContextId ).then( function( authResult ) {
					if ( authResult && authResult.authenticationState === 'succeeded' ) {
						// OTP completed — prefilled accelerated flow (IWT item 11).
						debugLog( 'Charitable PayPal Commerce: Fastlane authentication successful' );
						self.showFastlanePaymentSection( authResult.profileData, false );
					} else {
						// OTP cancelled/failed — card fields, no consent toggle (IWT item 12).
						debugLog( 'Charitable PayPal Commerce: Fastlane OTP cancelled — showing card fields without consent' );
						self.showFastlanePaymentSection( {}, true );
					}
				} ).catch( function( err ) {
					console.error( 'Charitable PayPal Commerce: Fastlane authentication error:', err );
					// Auth error — treat as cancelled OTP (no consent toggle).
					self.showFastlanePaymentSection( {}, true );
				} );

			} else {
				console.warn( 'Charitable PayPal Commerce: Fastlane authentication not available' );
				self.showFastlanePaymentSection( {}, true );
			}
		},

		/**
		 * Handle Fastlane guest flow.
		 */
		handleFastlaneGuestFlow: function( email ) {
			debugLog( 'Charitable PayPal Commerce: Starting Fastlane guest flow for:', email );
			// Show payment section for guest checkout.
			this.showFastlanePaymentSection( { email: email } );
		},

		/**
		 * Show Fastlane payment section.
		 *
		 * @param {Object}  profileData          Profile data from authenticated member (or empty object).
		 * @param {boolean} isMemberOtpCancelled  True when a Fastlane member cancelled OTP (no consent toggle).
		 */
		showFastlanePaymentSection: function( profileData, isMemberOtpCancelled ) {
			var self = this;

			// Hide email section, show payment section.
			this.$form.find( '.charitable-fastlane-email-section' ).hide();
			this.$form.find( '.charitable-fastlane-payment-section' ).show();

			var $payButton = this.$form.find( '#charitable-fastlane-pay-button' );

			// Render official FastlanePaymentComponent (IWT items 2, 5–12).
			if ( this.fastlaneInstance && typeof this.fastlaneInstance.FastlanePaymentComponent === 'function' ) {

				this.fastlaneInstance.FastlanePaymentComponent().then( function( paymentComponent ) {
					self.fastlanePaymentComponent = paymentComponent;
					return paymentComponent.render( '#charitable-fastlane-payment-component' );

				} ).then( function() {
					// Show submit button once component is rendered.
					$payButton.show();

					$payButton.off( 'click' ).on( 'click', function() {
						self.fallbackToStandardPayPal();
					} );

					debugLog( 'Charitable PayPal Commerce: Fastlane payment component rendered' );

				} ).catch( function( err ) {
					console.error( 'Charitable PayPal Commerce: Fastlane payment component error:', err );
					self.fallbackToStandardPayPal();
				} );

			} else {
				// SDK component not available — fall back to standard checkout.
				console.warn( 'Charitable PayPal Commerce: FastlanePaymentComponent not available, falling back' );
				self.fallbackToStandardPayPal();
			}
		},

		/**
		 * Create a PayPal order using a Fastlane single-use payment token.
		 *
		 * @param  {string} fastlaneToken  Token from FastlanePaymentComponent.getPaymentToken().
		 * @return {Promise<string>}        Resolves with the PayPal order ID.
		 */
		createFastlaneOrder: function( fastlaneToken ) {
			var self = this;
			var $form = this.$form;

			return new Promise( function( resolve, reject ) {
				var amount     = self.getDonationAmount();
				var campaignId = $form.find( 'input[name="campaign_id"]' ).val() || 0;

				$.ajax( {
					type: 'POST',
					url: self.config.ajax_url,
					data: {
						action:          'charitable_paypal_commerce_create_order',
						amount:          amount,
						campaign_id:     campaignId,
						currency:        self.config.currency,
						form_data:       $form.serialize(),
						payment_method:  'fastlane',
						fastlane_token:  fastlaneToken,
						nonce:           self.config.nonce
					},
					dataType: 'json',
					success: function( response ) {
						if ( response.success && response.data && response.data.order_id ) {
							resolve( response.data.order_id );
						} else {
							var msg = ( response.data && response.data.message ) ? response.data.message : self.config.i18n.error;
							reject( new Error( msg ) );
						}
					},
					error: function( xhr, status, error ) {
						reject( new Error( error ) );
					}
				} );
			} );
		},

		/**
		 * Validate email address.
		 */
		isValidEmail: function( email ) {
			var regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
			return regex.test( email );
		},

		/**
		 * Submit card payment.
		 */
		submitCardPayment: function() {
			var self = this;

			// Validate form first.
			if ( ! this.validateForm() ) {
				return;
			}

			// Validate card name.
			var cardName = this.$form.find( '#charitable-card-name' ).val();
			if ( ! cardName || cardName.trim() === '' ) {
				this.showCardError( this.config.i18n.required_fields || 'Please fill in all required fields.' );
				return;
			}

			if ( ! this.cardFieldsInstance ) {
				this.showCardError( this.config.i18n.error );
				return;
			}

			this.showProcessing();

			// Get billing address from form if available.
			var billingAddress = this.getBillingAddress();

			// Submit card fields.
			this.cardFieldsInstance.submit( {
				cardholderName: cardName,
				billingAddress: billingAddress
			} ).then( function( data ) {
				// Card payment submitted - approval handled by onApprove callback.
			} ).catch( function( err ) {
				self.hideProcessing();
				self.onCardError( err );
			} );
		},

		/**
		 * Get billing address from form.
		 *
		 * @return {Object} Billing address object.
		 */
		getBillingAddress: function() {
			var $form = this.$form;

			return {
				addressLine1: $form.find( 'input[name="address"]' ).val() || '',
				addressLine2: $form.find( 'input[name="address_2"]' ).val() || '',
				adminArea2: $form.find( 'input[name="city"]' ).val() || '', // City.
				adminArea1: $form.find( 'input[name="state"], select[name="state"]' ).val() || '', // State.
				postalCode: $form.find( 'input[name="postcode"]' ).val() || '',
				countryCode: $form.find( 'input[name="country"], select[name="country"]' ).val() || 'US'
			};
		},

		/**
		 * Handle card-specific errors.
		 *
		 * @param {Error|Object} err Error object.
		 */
		onCardError: function( err ) {
			this.hideProcessing();

			var message = this.config.i18n.card_error || 'Please check your card details and try again.';

			// Try to extract more specific error message.
			if ( err && err.message ) {
				message = err.message;
			} else if ( err && err.details && err.details.length > 0 ) {
				message = err.details[0].description || message;
			}

			console.error( 'Charitable PayPal Commerce: Card error:', err );
			this.showCardError( message );
		},

		/**
		 * Show card-specific error.
		 *
		 * @param {string} message Error message.
		 */
		showCardError: function( message ) {
			var $errors = this.$form.find( '.charitable-paypal-card-errors' );
			/* Build via .text() — `message` ultimately comes from the PayPal
			 * SDK error object (err.message / err.details[].description),
			 * which is loaded from a CDN and not contractually sanitized.
			 * Using .html() with string concatenation here would be an XSS
			 * vector if PayPal's SDK ever surfaces attacker-controlled
			 * fields. */
			var $p = $( '<p class="charitable-notice charitable-notice-error"></p>' );
			$p.text( message );
			$errors.empty().append( $p ).show();

			// Also use Charitable's notice system if available.
			if ( typeof CHARITABLE !== 'undefined' && CHARITABLE.notices ) {
				CHARITABLE.notices.add_error( message );
			}
		},

		/**
		 * Clear card errors.
		 */
		clearCardErrors: function() {
			var $errors = this.$form.find( '.charitable-paypal-card-errors' );
			$errors.empty().hide();
		},

		/**
		 * Validate the donation form.
		 *
		 * @return {boolean}
		 */
		validateForm: function() {
			var $form = this.$form;

			// Clear previous card errors.
			this.clearCardErrors();

			// Check for amount.
			var amount = this.getDonationAmount();
			if ( ! amount || amount <= 0 ) {
				this.showError( this.config.i18n.select_amount );
				return false;
			}

			// Basic required field validation for PayPal Commerce.
			// We don't trigger charitable:form:validate as it invokes credit card
			// validation which is not needed for PayPal payments.
			var isValid = true;

			// Check required donor fields. Checkboxes and radios have their
			// "value" attribute set (typically "1") regardless of checked
			// state, so testing .val() lets unchecked required checkboxes
			// (e.g. accept_terms) pass validation. Inspect :checked instead
			// for those input types.
			$form.find( '[required]' ).each( function() {
				var $field = $( this );
				var type   = ( $field.attr( 'type' ) || '' ).toLowerCase();
				var hasValue;

				if ( type === 'checkbox' || type === 'radio' ) {
					hasValue = $field.is( ':checked' );
				} else {
					hasValue = $field.val() && $field.val().toString().trim() !== '';
				}

				if ( ! hasValue ) {
					isValid = false;
					$field.addClass( 'charitable-field-error' );
				} else {
					$field.removeClass( 'charitable-field-error' );
				}
			} );

			if ( ! isValid ) {
				this.showError( this.config.i18n.required_fields || 'Please fill in all required fields.' );
			}

			return isValid;
		},

		/**
		 * Get the donation amount from the form.
		 *
		 * @return {number}
		 */
		getDonationAmount: function() {
			var $form = this.$form;
			var amount = 0;

			var $selectedAmount = $form.find( 'input[name="donation_amount"]:checked' );
			var selectedVal     = $selectedAmount.length ? $selectedAmount.val() : '';

			if ( selectedVal !== '' && selectedVal !== 'custom' ) {
				// Suggested amount — trust the radio's numeric value and stop. Reading
				// custom_donation_amount past this point would let a stale custom field
				// (remembered session, template default, currency-conversion artifact)
				// override the donor's actual selection.
				amount = parseFloat( selectedVal );
				return isNaN( amount ) ? 0 : amount;
			}

			// Either no radio is checked or the donor explicitly picked "custom".
			// Prefer the visible custom-donation-input over any sibling hidden mirror
			// since the visible field is what the donor (and the currency-conversion
			// addons) actually write to. Matches Charitable core's get_custom_amount
			// selector convention in charitable.js.
			var $customAmount = $form.find( 'input.custom-donation-input[name="custom_donation_amount"]' );
			if ( ! $customAmount.length ) {
				$customAmount = $form.find( 'input[name="custom_donation_amount"]' );
			}
			if ( $customAmount.length && $customAmount.val() ) {
				var customVal = parseFloat( $customAmount.val().toString().replace( /[^0-9.]/g, '' ) );
				if ( ! isNaN( customVal ) && customVal > 0 ) {
					amount = customVal;
				}
			}

			return isNaN( amount ) ? 0 : amount;
		},

		/**
		 * Create a PayPal order.
		 *
		 * @param {string} paymentMethod Payment method ('paypal' or 'card').
		 * @return {Promise}
		 */
		createOrder: function( paymentMethod ) {
			var self = this;
			var $form = this.$form;

			this.showProcessing();

			// Create PayPal order directly with form data.
			// The Charitable donation will be created when the order is captured.
			return new Promise( function( resolve, reject ) {
				var amount = self.getDonationAmount();
				var campaignId = $form.find( 'input[name="campaign_id"]' ).val() || 0;

				$.ajax( {
					type: 'POST',
					url: self.config.ajax_url,
					data: {
						action: 'charitable_paypal_commerce_create_order',
						amount: amount,
						campaign_id: campaignId,
						currency: self.config.currency,
						form_data: $form.serialize(),
						payment_method: paymentMethod || 'paypal',
						nonce: self.config.nonce
					},
					dataType: 'json',
					success: function( response ) {
						if ( response.success && response.data ) {
							// Handle order creation - return order ID to PayPal SDK.
							if ( response.data.order_id ) {
								// Hide overlay before handing back to SDK. PayPal's SDK
								// renders its own UI next (popup for wallet, inline iframe
								// for card fields); leaving the overlay up blocks inline
								// card-field input. onApprove re-shows it for capture.
								self.hideProcessing();
								resolve( response.data.order_id );
							} else {
								self.hideProcessing();
								var data    = response.data || {};
								var message = data.message || self.config.i18n.error;
								self.showError( message, { detail: data.detail, debug_id: data.debug_id } );
								reject( new Error( message ) );
							}
						} else {
							self.hideProcessing();
							var data    = response.data || {};
							var message = data.message || self.config.i18n.error;
							self.showError( message, { detail: data.detail, debug_id: data.debug_id } );
							reject( new Error( message ) );
						}
					},
					error: function( xhr, status, error ) {
						self.hideProcessing();
						self.showError( self.config.i18n.error );
						reject( new Error( error ) );
					}
				} );
			} );
		},

		/**
		 * Handle PayPal approval.
		 *
		 * @param {Object} data PayPal approval data.
		 * @return {Promise}
		 */
		onApprove: function( data ) {
			var self = this;

			this.showProcessing();

			return new Promise( function( resolve, reject ) {
				var ajaxData = {
					action: 'charitable_paypal_commerce_capture_order',
					order_id: data.orderID,
					nonce: self.config.nonce
				};

				$.ajax( {
					type: 'POST',
					url: self.config.ajax_url,
					data: ajaxData,
					dataType: 'json',
					success: function( response ) {
						self.hideProcessing();

						if ( response.success && response.data ) {
							if ( response.data.redirect ) {
								window.location.href = response.data.redirect;
							} else {
								self.showSuccess();
							}
							resolve();
						} else {
							var data    = response.data || {};
							var message = data.message || self.config.i18n.error;
							self.showError( message, { detail: data.detail, debug_id: data.debug_id } );
							reject( new Error( message ) );
						}
					},
					error: function( xhr, status, error ) {
						self.hideProcessing();
						self.showError( self.config.i18n.error );
						reject( new Error( error ) );
					}
				} );
			} );
		},

		/**
		 * Handle PayPal cancellation.
		 *
		 * @param {Object} data Cancellation data.
		 */
		onCancel: function( data ) {
			this.hideProcessing();
			// User cancelled - no need to show error.
		},

		/**
		 * Handle PayPal errors.
		 *
		 * @param {Error} err Error object.
		 */
		onError: function( err ) {
			this.hideProcessing();
			console.error( 'Charitable PayPal Commerce: Error:', err );

			/* The PayPal SDK fires onError after our createOrder Promise
			 * rejects. createOrder may have already shown a specific error
			 * from PayPal's response (e.g. "The merchant account is
			 * restricted."). Don't clobber it with the generic
			 * "There was an error processing your donation." Suppress this
			 * showError if a more specific one was surfaced in the last 3s. */
			if ( this._lastErrorAt && ( Date.now() - this._lastErrorAt ) < 3000 ) {
				return;
			}

			this.showError( this.config.i18n.error );
		},

		/**
		 * Handle gateway change.
		 *
		 * @param {Event} e Change event.
		 */
		onGatewayChange: function( e ) {
			var $form = $( e.target ).closest( '.charitable-donation-form' );
			var $container = $form.find( '#charitable-paypal-commerce-container' );
			var selectedGateway = $( e.target ).val();

			this.$form = $form;

			if ( selectedGateway === this.config.gateway_id ) {
				$container.show();
				this.toggleSubmitButton( false );

				if ( ! this.buttonsRendered ) {
					this.buildPaymentUI( $container );
				}
			} else {
				$container.hide();
				this.toggleSubmitButton( true );
			}
		},

		/**
		 * Handle form updated event.
		 *
		 * @param {Event}  e     Event object.
		 * @param {jQuery} $form Form element.
		 */
		onFormUpdated: function( e, $form ) {
			var $container = $form.find( '#charitable-paypal-commerce-container' );

			if ( $container.length && ! $container.data( 'initialized' ) ) {
				this.initForm( $form, $container );
			}
		},

		/**
		 * Toggle the submit button visibility.
		 *
		 * @param {boolean} show Whether to show the button.
		 */
		toggleSubmitButton: function( show ) {
			var $submit = this.$form.find( 'button[type="submit"], input[type="submit"]' );

			if ( show ) {
				$submit.show();
			} else {
				$submit.hide();
			}
		},

		/**
		 * Show processing state.
		 */
		showProcessing: function() {
			var $container = this.$form.find( '#charitable-paypal-commerce-container' );
			$container.addClass( 'processing' );

			// Add processing overlay if not exists.
			if ( ! $container.find( '.charitable-paypal-processing' ).length ) {
				$container.append( '<div class="charitable-paypal-processing"><span>' + this.config.i18n.processing + '</span></div>' );
			}

			// Disable card submit button.
			this.$form.find( '#charitable-card-submit' ).prop( 'disabled', true ).addClass( 'processing' );
		},

		/**
		 * Hide processing state.
		 */
		hideProcessing: function() {
			var $container = this.$form.find( '#charitable-paypal-commerce-container' );
			$container.removeClass( 'processing' );
			$container.find( '.charitable-paypal-processing' ).remove();

			// Re-enable card submit button.
			this.$form.find( '#charitable-card-submit' ).prop( 'disabled', false ).removeClass( 'processing' );
		},

		/**
		 * Show error message.
		 *
		 * Tier 1 — Charitable's site-wide notice system if it's exposed.
		 * Tier 2 — inline error rendered directly inside the donation form.
		 * Tier 3 — native alert(), only when no DOM target is available
		 *          (very unlikely path — defensive last resort).
		 *
		 * Records the time of the last error so onError() can suppress its
		 * own generic message if a more specific one was just surfaced.
		 *
		 * @param {string} message Error message.
		 */
		showError: function( message, options ) {
			options = options || {};
			this._lastErrorMessage = message;
			this._lastErrorAt      = Date.now();

			var primaryHandled = false;

			// Tier 1 — Charitable's site-wide notice system if it's exposed.
			if ( typeof CHARITABLE !== 'undefined' && CHARITABLE.notices && typeof CHARITABLE.notices.add_error === 'function' ) {
				CHARITABLE.notices.add_error( message );
				primaryHandled = true;
			}

			/* Tier 2 — inline error rendered directly inside the donation
			 * form. Always runs when there's a DOM target so we can append
			 * the admin-only detail block; if Tier 1 already showed the
			 * primary message, this Tier 2 path skips the main heading and
			 * only renders the detail (so admins see both the main notice
			 * via Charitable's chrome AND the technical detail inline). */
			if ( this.$form && this.$form.length ) {
				var $container = this.$form.find( '#charitable-paypal-commerce-container' );
				if ( $container.length ) {
					$container.find( '.charitable-paypal-error' ).remove();

					if ( ! primaryHandled ) {
						var $err = $(
							'<div class="charitable-notice charitable-notice-error charitable-paypal-error" role="alert" ' +
							'style="margin:1em 0;padding:0.75em 1em;border-left:4px solid #d63638;background:#fcf0f1;color:#1d2327;">' +
							'</div>'
						);
						$err.text( message );

						/* Admin-only technical detail. Donors don't see this;
						 * it gives signed-in admins enough to triage without
						 * checking the logs. */
						if ( options.detail ) {
							var $detail = $(
								'<small class="charitable-paypal-error-detail" ' +
								'style="display:block;margin-top:0.5em;color:#646970;font-size:0.85em;"></small>'
							);
							var detailText = options.detail;
							if ( options.debug_id ) {
								detailText += ' (PayPal debug ID: ' + options.debug_id + ')';
							}
							$detail.text( detailText );
							$err.append( $detail );
						}

						$container.prepend( $err );

						if ( $err[0] && typeof $err[0].scrollIntoView === 'function' ) {
							$err[0].scrollIntoView( { behavior: 'smooth', block: 'center' } );
						}

						setTimeout( function() {
							$err.fadeOut( 300, function() { $( this ).remove(); } );
						}, 10000 );
					} else if ( options.detail ) {
						/* Tier 1 owns the main message. Render only the
						 * admin detail block here so signed-in admins still
						 * get the raw PayPal text + debug_id without
						 * duplicating the donor-facing copy. */
						var $detailOnly = $(
							'<small class="charitable-paypal-error-detail charitable-paypal-error" ' +
							'style="display:block;margin:0.5em 0 1em;padding:0.5em 0.75em;color:#646970;font-size:0.85em;border-left:3px solid #c3c4c7;background:#f6f7f7;"></small>'
						);
						var detailTextOnly = options.detail;
						if ( options.debug_id ) {
							detailTextOnly += ' (PayPal debug ID: ' + options.debug_id + ')';
						}
						$detailOnly.text( detailTextOnly );
						$container.prepend( $detailOnly );
						setTimeout( function() {
							$detailOnly.fadeOut( 300, function() { $( this ).remove(); } );
						}, 10000 );
					}

					return;
				}
			}

			if ( primaryHandled ) {
				return;
			}

			/* Tier 3 — native alert. Donors should essentially never reach
			 * this path; it exists so a misconfigured page (e.g. a
			 * hand-built form embed missing the container) still surfaces
			 * the error rather than swallowing it. */
			window.alert( message );
		},

		/**
		 * Show success state.
		 */
		showSuccess: function() {
			var $container = this.$form.find( '#charitable-paypal-commerce-container' );
			$container.html( '<p class="charitable-notice charitable-notice-success">Donation successful!</p>' );
		},

		/**
		 * Check if error should trigger fallback from Fastlane.
		 *
		 * @param {Object} err Error object.
		 * @return {boolean} True if should fallback.
		 */
		shouldFallbackFromFastlane: function( err ) {
			// Check for specific Fastlane errors that indicate fallback is appropriate.
			var errorMessage = err.message || err.toString() || '';
			var errorLower = errorMessage.toLowerCase();

			// List of error conditions that should trigger fallback.
			var fallbackTriggers = [
				'fastlane',
				'initialization',
				'not available',
				'component not found',
				'client token',
				'authentication failed'
			];

			// If error mentions any fallback triggers, we should fallback.
			for ( var i = 0; i < fallbackTriggers.length; i++ ) {
				if ( errorLower.indexOf( fallbackTriggers[i] ) !== -1 ) {
					return true;
				}
			}

			// For any unrecognized error during Fastlane flow, fallback to be safe.
			return true;
		},

		/**
		 * Fallback to standard PayPal checkout.
		 */
		fallbackToStandardPayPal: function() {
			var self = this;

			debugLog( 'Charitable PayPal Commerce: Initiating fallback to standard PayPal' );

			// Hide Fastlane components.
			this.$form.find( '.charitable-paypal-payment-method-tab[data-method="fastlane"]' ).hide();
			this.$form.find( '#charitable-fastlane-container' ).hide();

			// Show and activate the PayPal tab.
			var $paypalTab = this.$form.find( '.charitable-paypal-payment-method-tab[data-method="paypal"]' );
			var $paypalContainer = this.$form.find( '#charitable-paypal-buttons-container' );

			if ( $paypalTab.length ) {
				$paypalTab.show().addClass( 'active' );

				// Remove active class from other tabs.
				this.$form.find( '.charitable-paypal-payment-method-tab' ).not( $paypalTab ).removeClass( 'active' );

				// Show PayPal container and hide others.
				$paypalContainer.show();
				this.$form.find( '.charitable-paypal-payment-pane' ).not( $paypalContainer ).hide();

				// Set selected method.
				this.selectedPaymentMethod = 'paypal';

				// Show a user-friendly message about the fallback.
				this.showError( 'We\'ve switched you to our standard PayPal checkout for the best experience.' );

				debugLog( 'Charitable PayPal Commerce: Fallback to standard PayPal completed' );
			} else {
				// Last resort - show general error.
				this.onError( new Error( 'Payment method not available. Please refresh the page and try again.' ) );
			}
		},
	};

	// Initialize when DOM is ready.
	$( document ).ready( function() {
		CharitablePayPalCommerce.init();
	} );

} )( jQuery );
