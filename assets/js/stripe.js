// assets/js/stripe.js
document.addEventListener('DOMContentLoaded', function() {
    if (typeof stripe === 'undefined') {
        console.error('Stripe.js not loaded');
        return;
    }

    // Create Stripe Elements instance
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

    // Mount card element
    const cardElement = document.getElementById('card-element');
    if (cardElement) {
        card.mount('#card-element');
    }

    // Handle form submission
    const form = document.getElementById('payment-form');
    if (!form) return;

    form.addEventListener('submit', async function(event) {
        event.preventDefault();

        // Disable the submit button to prevent repeated clicks
        const submitButton = document.querySelector('#payment-form button[type="submit"]');
        if (submitButton) {
            submitButton.disabled = true;
        }

        try {
            const {token, error} = await stripe.createToken(card);

            if (error) {
                // Handle errors
                const errorElement = document.getElementById('card-errors');
                if (errorElement) {
                    errorElement.textContent = error.message;
                }
                
                // Re-enable the submit button
                if (submitButton) {
                    submitButton.disabled = false;
                }
            } else {
                // Add the token to the form
                const hiddenInput = document.createElement('input');
                hiddenInput.setAttribute('type', 'hidden');
                hiddenInput.setAttribute('name', 'stripeToken');
                hiddenInput.setAttribute('value', token.id);
                form.appendChild(hiddenInput);

                // Submit the form
                form.submit();
            }
        } catch (err) {
            console.error('Payment failed:', err);
            
            // Re-enable the submit button
            if (submitButton) {
                submitButton.disabled = false;
            }
            
            // Show error message
            const errorElement = document.getElementById('card-errors');
            if (errorElement) {
                errorElement.textContent = 'An unexpected error occurred. Please try again.';
            }
        }
    });

    // Handle real-time validation errors
    card.addEventListener('change', function(event) {
        const displayError = document.getElementById('card-errors');
        if (displayError) {
            displayError.textContent = event.error ? event.error.message : '';
        }
    });
});