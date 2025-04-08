// assets/js/cart.js
// Funcționalități JavaScript specifice pentru coșul de cumpărături

document.addEventListener('DOMContentLoaded', function() {
    initCartFunctionality();
});

// Inițializare funcționalități coș
function initCartFunctionality() {
    // Ascultă schimbările în cantități
    setupQuantityListeners();
    
    // Ascultă butoanele de ștergere
    setupRemoveButtons();
    
    // Validare formular comandă
    setupCheckoutValidation();
}

// Configurare ascultători pentru cantități
function setupQuantityListeners() {
    const quantityInputs = document.querySelectorAll('.cart-quantity-input');
    quantityInputs.forEach(function(input) {
        input.addEventListener('change', function() {
            const productId = this.closest('.cart-item').getAttribute('data-product-id');
            let quantity = parseInt(this.value);
            
            // Validare cantitate minimă și maximă
            if (isNaN(quantity) || quantity < 1) {
                quantity = 1;
                this.value = 1;
            }
            
            const maxQuantity = 9999;
            if (quantity > maxQuantity) {
                quantity = maxQuantity;
                this.value = maxQuantity;
            }
            
            // Actualizare coș
            updateCartQuantity(productId, quantity);
        });
    });
    
    // Butoane incrementare/decrementare
    const quantityButtons = document.querySelectorAll('.quantity-btn');
    quantityButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const input = this.closest('.cart-item-actions').querySelector('.cart-quantity-input');
            let quantity = parseInt(input.value);
            
            if (this.classList.contains('quantity-decrease')) {
                quantity = Math.max(1, quantity - 1);
            } else if (this.classList.contains('quantity-increase')) {
                quantity = Math.min(9999, quantity + 1);
            }
            
            input.value = quantity;
            
            // Declanșează evenimentul change pentru a actualiza coșul
            const changeEvent = new Event('change');
            input.dispatchEvent(changeEvent);
        });
    });
}

// Configurare butoane de ștergere
function setupRemoveButtons() {
    const removeButtons = document.querySelectorAll('.remove-from-cart');
    removeButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const productId = this.closest('.cart-item').getAttribute('data-product-id');
            removeFromCart(productId);
        });
    });
}

// Configurare validare formular checkout
function setupCheckoutValidation() {
    const checkoutForm = document.querySelector('#checkout-form');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function(e) {
            // Verifică dacă există produse în coș
            const cartItems = document.querySelectorAll('.cart-item');
            if (cartItems.length === 0) {
                e.preventDefault();
                showAlert('Nu puteți plasa o comandă cu coșul gol.', 'error');
                return;
            }
            
            // Verifică dacă este selectată o locație
            const locationSelect = document.querySelector('#location_id');
            if (locationSelect && locationSelect.value === '') {
                e.preventDefault();
                showAlert('Vă rugăm să selectați o locație pentru livrare.', 'error');
                locationSelect.classList.add('border-red-500');
                return;
            }
            
            // Verifică termenii și condițiile
            const termsCheckbox = document.querySelector('#terms');
            if (termsCheckbox && !termsCheckbox.checked) {
                e.preventDefault();
                showAlert('Trebuie să acceptați termenii și condițiile pentru a continua.', 'error');
                return;
            }
        });
    }
}

// Funcție pentru golirea coșului
function clearCart() {
    if (confirm('Sunteți sigur că doriți să goliți coșul de cumpărături?')) {
        // Trimite cerere AJAX pentru golire coș
        fetch('clear_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Golire reușită, actualizează interfața
                document.querySelector('.cart-container').innerHTML = '<p class="text-center py-4">Coșul dvs. este gol.</p>';
                document.querySelector('.cart-actions').classList.add('hidden');
                
                // Actualizare contor coș
                const cartCounter = document.querySelector('.cart-counter');
                if (cartCounter) {
                    cartCounter.textContent = '0';
                    cartCounter.classList.add('hidden');
                }
                
                showAlert('Coșul a fost golit cu succes.', 'success', 3000);
            } else {
                // Eroare la golire
                showAlert(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Eroare la comunicarea cu serverul.', 'error');
        });
    }
}

// Funcție pentru actualizarea selectării locației
function updateLocationFields() {
    const locationSelect = document.querySelector('#location_id');
    if (locationSelect) {
        const locationInfoContainer = document.querySelector('#location-info');
        
        locationSelect.addEventListener('change', function() {
            const locationId = this.value;
            
            if (locationId) {
                // Afișare informații locație selectată
                fetch(`get_location_info.php?id=${locationId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            locationInfoContainer.innerHTML = `
                                <div class="mt-4 p-4 bg-gray-50 rounded-md">
                                    <h4 class="font-medium">Adresa de livrare:</h4>
                                    <p class="mt-1">${data.location.name}</p>
                                    <p>${data.location.address}</p>
                                    <p class="mt-2">Persoană de contact: ${data.location.contact_person || 'N/A'}</p>
                                    <p>Telefon: ${data.location.phone || 'N/A'}</p>
                                </div>
                            `;
                            locationInfoContainer.classList.remove('hidden');
                        } else {
                            locationInfoContainer.innerHTML = '';
                            locationInfoContainer.classList.add('hidden');
                            showAlert('Eroare la obținerea informațiilor despre locație.', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showAlert('Eroare la comunicarea cu serverul.', 'error');
                    });
            } else {
                // Ascunde informațiile locației
                locationInfoContainer.innerHTML = '';
                locationInfoContainer.classList.add('hidden');
            }
        });
    }
}

// Exportăm funcțiile pentru utilizare globală
window.clearCart = clearCart;