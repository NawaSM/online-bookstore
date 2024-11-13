document.addEventListener('DOMContentLoaded', function() {
    const stripe = Stripe(PUBLISHED_KEY);
    const elements = stripe.elements();

    const card = elements.create('card', {
        style: {
            base: {
                fontSize: '16px',
                color: '#32325d',
                fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
                '::placeholder': {
                    color: '#aab7c4'
                }
            },
            invalid: {
                color: '#fa755a',
                iconColor: '#fa755a'
            }
        }
    });

    card.mount('#card-element');

    card.addEventListener('change', function(event) {
        const displayError = document.getElementById('card-errors');
        if (event.error) {
            displayError.textContent = event.error.message;
        } else {
            displayError.textContent = '';
        }
    });

    // Payment method selection
    document.querySelectorAll('input[name="payment_method"]').forEach(input => {
        input.addEventListener('change', function() {
            const cardInputs = document.getElementById('cardInputs');
            const paymentMethods = document.querySelectorAll('.payment-method');

            paymentMethods.forEach(method => {
                method.classList.remove('selected');
            });

            this.closest('.payment-method').classList.add('selected');

            if (this.value === 'credit_card') {
                cardInputs.classList.add('active');
            } else {
                cardInputs.classList.remove('active');
            }
        });
    });

    // Form submission
    const form = document.getElementById('payment-form');
    form.addEventListener('submit', handleFormSubmit);

    async function handleFormSubmit(event) {
        event.preventDefault();

        const submitButton = form.querySelector('button[type="submit"]');
        const buttonText = submitButton.querySelector('.button-text');
        const spinner = submitButton.querySelector('.spinner');

        submitButton.disabled = true;
        buttonText.classList.add('hidden');
        spinner.classList.remove('hidden');

        if (!validateForm()) {
            resetButton(submitButton, buttonText, spinner);
            return false;
        }
        
        const selectedPaymentMethod = document.querySelector('input[name="payment_method"]:checked').value;

        try {
            if (selectedPaymentMethod === 'credit_card') {
                // Get payment intent client secret
                const response = await fetch('/onlinebookstore/create-payment-intent.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        amount: TOTAL_AMOUNT,
                        shipping: {
                            name: document.getElementById('shipping_name').value,
                            address: {
                                line1: document.getElementById('shipping_address').value,
                                city: document.getElementById('shipping_city').value,
                                state: document.getElementById('shipping_state').value,
                                postal_code: document.getElementById('shipping_zip').value,
                                country: document.getElementById('shipping_country').value
                            }
                        }
                    })
                });

                const paymentData = await response.json();

                if (paymentData.error) {
                    handlePaymentError(paymentData.error);
                    resetButton(submitButton, buttonText, spinner);
                    return;
                }

                const { error: confirmError } = await stripe.confirmCardPayment(
                    paymentData.clientSecret,
                    {
                        payment_method: {
                            card: card,
                            billing_details: {
                                name: document.getElementById('shipping_name').value,
                                email: document.getElementById('shipping_email').value
                            }
                        }
                    }
                );

                if (confirmError) {
                    handlePaymentError(confirmError.message);
                    resetButton(submitButton, buttonText, spinner);
                } else {
                    // Payment successful, redirect to success page
                    window.location.href = `${BASE_URL}/order-success.php?order_id=${paymentData.orderId}`;
                }
            } else if (selectedPaymentMethod === 'paypal') {
                // Your existing PayPal logic
                appendPaymentMethod('paypal');
                form.action = 'pages/process_paypal_payment.php';
                form.submit();
            }
        } catch (err) {
            console.error('Payment error:', err);
            handlePaymentError('An unexpected error occurred. Please try again.');
            resetButton(submitButton, buttonText, spinner);
        }
    }

    function validateForm() {
        const requiredFields = [
            'shipping_name',
            'shipping_email',
            'shipping_phone',
            'shipping_address',
            'shipping_city',
            'shipping_state',
            'shipping_country',
            'shipping_zip'
        ];

        let isValid = true;
        requiredFields.forEach(field => {
            const input = document.getElementById(field);
            if (!input.value.trim()) {
                input.classList.add('error');
                isValid = false;
            } else {
                input.classList.remove('error');
            }
        });

        return isValid;
    }

    function handlePaymentError(message) {
        const errorElement = document.getElementById('card-errors');
        errorElement.textContent = message;
    }

    function resetButton(submitButton, buttonText, spinner) {
        submitButton.disabled = false;
        buttonText.classList.remove('hidden');
        spinner.classList.add('hidden');
    }

    function appendTokenToForm(tokenId) {
        const hiddenInput = document.createElement('input');
        hiddenInput.setAttribute('type', 'hidden');
        hiddenInput.setAttribute('name', 'stripeToken');
        hiddenInput.setAttribute('value', tokenId);
        form.appendChild(hiddenInput);
    }

    function appendPaymentMethod(method) {
        const paymentMethodInput = document.createElement('input');
        paymentMethodInput.setAttribute('type', 'hidden');
        paymentMethodInput.setAttribute('name', 'payment_method');
        paymentMethodInput.setAttribute('value', method);
        form.appendChild(paymentMethodInput);
    }
});