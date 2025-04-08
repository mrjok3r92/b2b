// assets/js/main.js
// Funcționalități JavaScript comune pentru aplicație

// Funcție executată la încărcarea documentului
document.addEventListener('DOMContentLoaded', function() {
    // Inițializare componente
    initComponents();
    
    // Setare evenimente
    setupEventListeners();
    
    // Auto-închidere pentru alerte
    setupAutoCloseAlerts();
});

// Inițializare componente UI
function initComponents() {
    // Inițializare tooltip-uri
    setupTooltips();
    
    // Inițializare dropdown-uri
    setupDropdowns();
    
    // Inițializare tab-uri (dacă există)
    setupTabs();
}

// Setare evenimente pentru elementele interactive
function setupEventListeners() {
    // Confirmare pentru ștergere
    setupDeleteConfirmations();
    
    // Validare formulare
    setupFormValidation();
    
    // Toggle pentru sidebar (dacă există)
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('sidebar-collapsed');
            document.querySelector('.content').classList.toggle('content-expanded');
        });
    }
}

// Configurare tooltip-uri
function setupTooltips() {
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(function(tooltip) {
        tooltip.setAttribute('title', tooltip.getAttribute('data-tooltip'));
    });
}

// Configurare dropdown-uri
function setupDropdowns() {
    const dropdowns = document.querySelectorAll('.dropdown-toggle');
    dropdowns.forEach(function(dropdown) {
        dropdown.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const menu = this.nextElementSibling;
            menu.classList.toggle('hidden');
            
            // Ascunde celelalte dropdown-uri
            dropdowns.forEach(function(otherDropdown) {
                if (otherDropdown !== dropdown) {
                    const otherMenu = otherDropdown.nextElementSibling;
                    if (otherMenu && !otherMenu.classList.contains('hidden')) {
                        otherMenu.classList.add('hidden');
                    }
                }
            });
        });
    });
    
    // Ascunde dropdown-urile când se face click în altă parte
    document.addEventListener('click', function(e) {
        dropdowns.forEach(function(dropdown) {
            const menu = dropdown.nextElementSibling;
            if (menu && !menu.classList.contains('hidden') && !dropdown.contains(e.target) && !menu.contains(e.target)) {
                menu.classList.add('hidden');
            }
        });
    });
}

// Configurare tab-uri
function setupTabs() {
    const tabGroups = document.querySelectorAll('.tabs');
    tabGroups.forEach(function(tabGroup) {
        const tabs = tabGroup.querySelectorAll('.tab');
        const tabContents = document.querySelectorAll('.tab-content[data-tab-group="' + tabGroup.getAttribute('data-tab-group') + '"]');
        
        tabs.forEach(function(tab) {
            tab.addEventListener('click', function() {
                // Dezactivează toate tab-urile
                tabs.forEach(function(t) {
                    t.classList.remove('tab-active');
                });
                
                // Ascunde toate conținuturile
                tabContents.forEach(function(content) {
                    content.classList.add('hidden');
                });
                
                // Activează tab-ul curent
                this.classList.add('tab-active');
                
                // Afișează conținutul corespunzător
                const tabContent = document.querySelector('.tab-content[data-tab="' + this.getAttribute('data-tab') + '"]');
                if (tabContent) {
                    tabContent.classList.remove('hidden');
                }
            });
        });
    });
}

// Configurare confirmare ștergere
function setupDeleteConfirmations() {
    const deleteButtons = document.querySelectorAll('.delete-confirm');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            if (!confirm('Sunteți sigur că doriți să ștergeți acest element? Această acțiune nu poate fi anulată.')) {
                e.preventDefault();
            }
        });
    });
}

// Configurare validare formulare
function setupFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(function(field) {
                // Reset
                field.classList.remove('border-red-500');
                const errorElement = field.parentNode.querySelector('.error-message');
                if (errorElement) {
                    errorElement.remove();
                }
                
                // Validare
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('border-red-500');
                    
                    const errorMessage = document.createElement('p');
                    errorMessage.classList.add('error-message', 'text-red-500', 'text-xs', 'mt-1');
                    errorMessage.innerText = 'Acest câmp este obligatoriu.';
                    field.parentNode.appendChild(errorMessage);
                }
            });
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    });
}

// Configurare închidere automată alertele
function setupAutoCloseAlerts() {
    const alerts = document.querySelectorAll('.alert[data-auto-close]');
    alerts.forEach(function(alert) {
        const timeout = parseInt(alert.getAttribute('data-auto-close'));
        if (!isNaN(timeout) && timeout > 0) {
            setTimeout(function() {
                alert.classList.add('fade-out');
                setTimeout(function() {
                    alert.remove();
                }, 300);
            }, timeout);
        }
    });
}

// Funcție pentru validarea input-urilor
function validateInput(input, pattern, errorMessage) {
    const value = input.value.trim();
    const isValid = pattern.test(value);
    
    const errorElement = input.parentNode.querySelector('.error-message');
    if (errorElement) {
        errorElement.remove();
    }
    
    if (!isValid) {
        input.classList.add('border-red-500');
        
        const errorMsg = document.createElement('p');
        errorMsg.classList.add('error-message', 'text-red-500', 'text-xs', 'mt-1');
        errorMsg.innerText = errorMessage;
        input.parentNode.appendChild(errorMsg);
        
        return false;
    } else {
        input.classList.remove('border-red-500');
        return true;
    }
}

// Funcție pentru afișarea unui mesaj de alertă
function showAlert(message, type = 'info', autoClose = 0) {
    const alertContainer = document.querySelector('.alert-container');
    if (!alertContainer) {
        return;
    }
    
    const alertClass = type === 'success' ? 'bg-green-100 border-green-500 text-green-700' :
                       type === 'error' ? 'bg-red-100 border-red-500 text-red-700' :
                       type === 'warning' ? 'bg-yellow-100 border-yellow-500 text-yellow-700' :
                       'bg-blue-100 border-blue-500 text-blue-700';
    
    const icon = type === 'success' ? '<i class="fas fa-check-circle"></i>' :
                type === 'error' ? '<i class="fas fa-exclamation-circle"></i>' :
                type === 'warning' ? '<i class="fas fa-exclamation-triangle"></i>' :
                '<i class="fas fa-info-circle"></i>';
    
    const alert = document.createElement('div');
    alert.className = `alert ${alertClass} px-4 py-3 rounded border fade-in mb-4`;
    alert.setAttribute('role', 'alert');
    
    if (autoClose > 0) {
        alert.setAttribute('data-auto-close', autoClose);
    }
    
    alert.innerHTML = `
        <div class="flex">
            <div class="py-1 mr-2">${icon}</div>
            <div>${message}</div>
            <div class="ml-auto">
                <button type="button" class="alert-close text-gray-400 hover:text-gray-500">
                    <span>&times;</span>
                </button>
            </div>
        </div>
    `;
    
    alertContainer.appendChild(alert);
    
    // Event pentru butonul de închidere
    alert.querySelector('.alert-close').addEventListener('click', function() {
        alert.classList.add('fade-out');
        setTimeout(function() {
            alert.remove();
        }, 300);
    });
    
    // Auto-închidere dacă este specificat
    if (autoClose > 0) {
        setTimeout(function() {
            if (alert.parentNode) {
                alert.classList.add('fade-out');
                setTimeout(function() {
                    if (alert.parentNode) {
                        alert.remove();
                    }
                }, 300);
            }
        }, autoClose);
    }
}

// Funcție pentru formatul de monedă
function formatCurrency(amount) {
    return amount.toLocaleString('ro-RO', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

// Funcție pentru actualizarea totală a coșului
function updateCartTotal() {
    let total = 0;
    const cartItems = document.querySelectorAll('.cart-item');
    
    cartItems.forEach(function(item) {
        const price = parseFloat(item.querySelector('.item-price').getAttribute('data-price'));
        const quantity = parseInt(item.querySelector('.cart-quantity-input').value);
        const itemTotal = price * quantity;
        
        item.querySelector('.item-total').textContent = formatCurrency(itemTotal) + ' Lei';
        total += itemTotal;
    });
    
    document.querySelector('.cart-total').textContent = formatCurrency(total) + ' Lei';
    document.querySelector('#total_amount').value = total.toFixed(2);
}

// Funcție pentru actualizarea cantității în coș
function updateCartQuantity(productId, quantity) {
    // Trimite cerere AJAX pentru actualizarea coșului
    fetch('update_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&quantity=${quantity}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Actualizare reușită
            updateCartTotal();
        } else {
            // Eroare la actualizare
            showAlert(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Eroare la comunicarea cu serverul.', 'error');
    });
}

// Funcție pentru ștergerea unui element din coș
function removeFromCart(productId) {
    if (confirm('Sunteți sigur că doriți să eliminați acest produs din coș?')) {
        // Trimite cerere AJAX pentru ștergere
        fetch('remove_from_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `product_id=${productId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Eliminare reușită, șterge elementul din DOM
                const cartItem = document.querySelector(`.cart-item[data-product-id="${productId}"]`);
                if (cartItem) {
                    cartItem.remove();
                    updateCartTotal();
                    
                    // Verifică dacă coșul este gol
                    const cartItems = document.querySelectorAll('.cart-item');
                    if (cartItems.length === 0) {
                        document.querySelector('.cart-container').innerHTML = '<p class="text-center py-4">Coșul dvs. este gol.</p>';
                        document.querySelector('.cart-actions').classList.add('hidden');
                    }
                }
            } else {
                // Eroare la eliminare
                showAlert(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Eroare la comunicarea cu serverul.', 'error');
        });
    }
}

// Funcție pentru adăugarea în coș
function addToCart(productId, quantity = 1) {
    // Trimite cerere AJAX pentru adăugare
    fetch('add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&quantity=${quantity}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Adăugare reușită
            showAlert(data.message, 'success', 3000);
            
            // Actualizare contor coș
            const cartCounter = document.querySelector('.cart-counter');
            if (cartCounter) {
                cartCounter.textContent = data.cartCount;
                cartCounter.classList.remove('hidden');
            }
        } else {
            // Eroare la adăugare
            showAlert(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Eroare la comunicarea cu serverul.', 'error');
    });
}

// Export funcții pentru utilizare globală
window.validateInput = validateInput;
window.showAlert = showAlert;
window.formatCurrency = formatCurrency;
window.updateCartTotal = updateCartTotal;
window.updateCartQuantity = updateCartQuantity;
window.removeFromCart = removeFromCart;
window.addToCart = addToCart;