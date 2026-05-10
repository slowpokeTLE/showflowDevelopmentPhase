<?php
require 'constants.php';
require 'session_handler.php';
require 'db.php';

if (!hasRole(ROLE_USER)) {
    header('Location: user-login.php');
    exit();
}

$u_id = $_SESSION['u_id'];

// Fetch all theatres
$query = "SELECT t_id, theatre_name, location FROM theatre ORDER BY theatre_name ASC";
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();
$theatres = [];
while ($row = $result->fetch_assoc()) {
    $theatres[] = $row;
}
$stmt->close();

// Convert theatres to JSON for JavaScript
$theatres_json = json_encode($theatres);
?>
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facilities - ShowFlow</title>
    <link rel="icon" type="image/png" href="showflowicon.png">
    <link rel="stylesheet" href="netflix-theme.css">
    <style>
        .facilities-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .header {
            font-size: 28px;
            font-weight: bold;
            color: var(--text-primary);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-links {
            display: flex;
            gap: 1rem;
        }

        .nav-link {
            padding: 0.75rem 1.5rem;
            background: var(--bg-secondary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .nav-link:hover {
            background: var(--accent-red);
            border-color: var(--accent-red);
        }

        .tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            border-bottom: 2px solid var(--border-color);
        }

        .tab-button {
            padding: 1rem 1.5rem;
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }

        .tab-button:hover {
            color: var(--text-primary);
        }

        .tab-button.active {
            color: var(--accent-red);
            border-bottom-color: var(--accent-red);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Food Ordering Tab */
        .theatre-selector {
            background: var(--bg-secondary);
            padding: 2rem;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            margin-bottom: 2rem;
        }

        .selector-label {
            color: var(--text-primary);
            font-weight: bold;
            margin-bottom: 1rem;
        }

        .theatre-select {
            width: 100%;
            max-width: 400px;
            padding: 0.75rem;
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            color: var(--text-primary);
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        /* Ensure dropdown options are readable — hardcoded because browsers ignore
           CSS variables on native <option> elements */
        .theatre-select option {
            color: #ffffff !important;
            background-color: #1a1a2e !important;
        }

        .theatre-select option:disabled,
        .theatre-select option[value=""] {
            color: #aaaaaa !important;
            background-color: #1a1a2e !important;
        }

        .theatre-select:focus {
            outline: none;
            border-color: var(--accent-red);
        }

        .food-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .food-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            transition: all 0.3s ease;
            cursor: pointer;
            display: flex;
            flex-direction: column;
        }

        .food-card:hover {
            transform: scale(1.05);
            border-color: var(--accent-red);
            box-shadow: 0 8px 20px rgba(229, 9, 20, 0.2);
        }

        .food-icon {
            font-size: 48px;
            margin-bottom: 1rem;
            text-align: center;
        }

        .food-name {
            font-size: 18px;
            font-weight: bold;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .food-price {
            font-size: 20px;
            font-weight: bold;
            color: var(--accent-red);
            margin-bottom: 1rem;
        }

        .food-qty {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            margin-bottom: 1rem;
        }

        .qty-btn {
            width: 30px;
            height: 30px;
            border: 1px solid var(--border-color);
            background: var(--bg-primary);
            color: var(--text-primary);
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .qty-btn:hover {
            border-color: var(--accent-red);
            color: var(--accent-red);
        }

        .qty-input {
            width: 50px;
            text-align: center;
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            border-radius: 4px;
            padding: 0.25rem;
        }

        .add-btn {
            width: 100%;
            padding: 0.75rem;
            background: var(--accent-red);
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .add-btn:hover {
            background: #d40812;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-secondary);
        }

        .empty-icon {
            font-size: 64px;
            margin-bottom: 1rem;
        }

        /* Complaints Tab */
        .complaint-section {
            background: var(--bg-secondary);
            padding: 2rem;
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }

        .complaint-form {
            max-width: 600px;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            color: var(--text-primary);
            font-weight: bold;
            margin-bottom: 0.5rem;
            font-size: 14px;
        }

        .form-input,
        .form-textarea,
        .form-select {
            width: 100%;
            padding: 0.75rem;
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            color: var(--text-primary);
            font-size: 14px;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .form-textarea {
            resize: vertical;
            min-height: 120px;
        }

        .form-input:focus,
        .form-textarea:focus,
        .form-select:focus {
            outline: none;
            border-color: var(--accent-red);
        }

        .form-submit {
            background: var(--accent-red);
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .form-submit:hover {
            background: #d40812;
        }

        .success-message {
            background: rgba(45, 90, 45, 0.2);
            border: 1px solid #90EE90;
            color: #90EE90;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }

        .error-message {
            background: rgba(139, 0, 0, 0.2);
            border: 1px solid #ff6b6b;
            color: #ff6b6b;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }

        .order-summary {
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 2rem;
        }

        .summary-title {
            font-size: 18px;
            font-weight: bold;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-primary);
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            padding: 1rem 0 0;
            font-size: 18px;
            font-weight: bold;
            color: var(--accent-red);
        }

        .order-btn {
            width: 100%;
            margin-top: 1rem;
            padding: 0.75rem;
            background: var(--accent-red);
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .order-btn:hover {
            background: #d40812;
        }

        .order-btn:disabled {
            background: #666;
            cursor: not-allowed;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .facilities-container {
                padding: 1rem;
            }

            .header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .food-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 1rem;
            }

            .tabs {
                flex-wrap: wrap;
            }

            .tab-button {
                padding: 0.75rem 1rem;
                font-size: 14px;
            }
        }
    </style>
</head>
<body style="background: var(--bg-primary);">
    <div class="facilities-container">
        <div class="header">
            <span>🎭 Theatre Facilities</span>
            <div class="nav-links">
                <a href="index.php" class="nav-link">🏠 Home</a>
                <a href="booking.php" class="nav-link">🎫 Bookings</a>
                <a href="user-profile.php" class="nav-link">👤 Profile</a>
                <a href="logout.php" class="nav-link">🚪 Logout</a>
            </div>
        </div>

        <!-- Step 1: Theatre Selection -->
        <div class="theatre-selector" id="theatreSelectionStep">
            <div class="selector-label">Select a Theatre</div>
            <select class="theatre-select" id="theatreSelect" onchange="handleTheatreSelect()">
                <option value="">-- Choose Theatre --</option>
                <?php foreach ($theatres as $t): ?>
                    <option value="<?php echo $t['t_id']; ?>">
                        <?php echo htmlspecialchars($t['theatre_name']); ?> - <?php echo htmlspecialchars($t['location']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Step 2: Options (appears after theatre selection) -->
        <div id="optionsStep" style="display: none; margin-top: 3rem;">
            <div style="text-align: center; margin-bottom: 2rem;">
                <h2 style="color: var(--text-primary); font-size: 24px;">What would you like to do?</h2>
                <p style="color: var(--text-secondary);">at <span id="theatreNameDisplay" style="color: var(--accent-red); font-weight: bold;"></span></p>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; margin-bottom: 3rem;">
                <!-- Food Ordering Card -->
                <div class="action-card" style="padding: 2rem; background: linear-gradient(135deg, var(--secondary-bg) 0%, var(--tertiary-bg) 100%); border: 2px solid var(--border-color); border-radius: 12px; cursor: pointer; transition: all 0.3s ease; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center;" onmouseover="this.style.borderColor='var(--accent-red)'; this.style.boxShadow='0 8px 24px rgba(229, 9, 20, 0.3)'" onmouseout="this.style.borderColor='var(--border-color)'; this.style.boxShadow='none'" onclick="openFoodModal()">
                    <div style="font-size: 56px; margin-bottom: 1rem;">🍿</div>
                    <div style="font-size: 22px; font-weight: bold; color: var(--text-primary); margin-bottom: 0.5rem;">Buy Food</div>
                    <div style="font-size: 12px; color: var(--text-secondary);">Order snacks & beverages</div>
                </div>

                <!-- Complaint Box Card -->
                <div class="action-card" style="padding: 2rem; background: linear-gradient(135deg, var(--secondary-bg) 0%, var(--tertiary-bg) 100%); border: 2px solid var(--border-color); border-radius: 12px; cursor: pointer; transition: all 0.3s ease; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center;" onmouseover="this.style.borderColor='var(--accent-red)'; this.style.boxShadow='0 8px 24px rgba(229, 9, 20, 0.3)'" onmouseout="this.style.borderColor='var(--border-color)'; this.style.boxShadow='none'" onclick="openComplaintModal()">
                    <div style="font-size: 56px; margin-bottom: 1rem;">💬</div>
                    <div style="font-size: 22px; font-weight: bold; color: var(--text-primary); margin-bottom: 0.5rem;">Submit Complaint</div>
                    <div style="font-size: 12px; color: var(--text-secondary);">Report issues or feedback</div>
                </div>
            </div>

            <!-- My Orders Section -->
            <div class="theatre-selector">
                <h3 style="color: var(--text-primary); margin-bottom: 1.5rem;">📋 My Recent Orders</h3>
                <div id="myOrdersList" style="color: var(--text-secondary); text-align: center; padding: 2rem;">
                    Loading orders...
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: Buy Food -->
    <div id="foodModal" class="modal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h2 class="modal-title">🍿 Buy Food</h2>
                <button class="modal-close" onclick="closeFoodModal()">✕</button>
            </div>
            <div style="padding: 1.5rem;">
                <div id="foodListContainer" style="color: var(--text-secondary);">
                    Loading food items...
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: Complaint Box -->
    <div id="complaintModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h2 class="modal-title">💬 Submit Complaint</h2>
                <button class="modal-close" onclick="closeComplaintModal()">✕</button>
            </div>
            <div style="padding: 1.5rem;">
                <form id="complaintForm" onsubmit="submitComplaint(event)" style="display: none;">
                    <div class="form-group">
                        <label class="form-label">Your Complaint</label>
                        <textarea name="message" class="form-textarea" placeholder="Please describe your issue or feedback..." required style="min-height: 150px;"></textarea>
                    </div>
                    <button type="submit" class="form-submit">Submit Complaint</button>
                </form>
                <div id="complaintNotice" style="color: var(--text-secondary); text-align: center; padding: 2rem;">
                    Select a theatre to submit a complaint
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: Order Confirmation -->
    <div id="confirmationModal" class="modal">
        <div class="modal-content" style="max-width: 500px; text-align: center;">
            <div style="padding: 2rem;">
                <div style="font-size: 64px; margin-bottom: 1rem;">✅</div>
                <h2 class="modal-title" style="color: var(--accent-red); margin-bottom: 1rem;">Order Confirmed!</h2>
                <div style="background: var(--secondary-bg); padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem;">
                    <div style="color: var(--text-secondary); font-size: 12px; margin-bottom: 0.5rem;">Order Reference</div>
                    <div id="confirmationReference" style="font-size: 24px; font-weight: bold; color: var(--accent-red); font-family: monospace;">ORDER-0000</div>
                </div>
                <div style="color: var(--text-secondary); margin-bottom: 1.5rem;">
                    <p>Your order has been placed successfully!</p>
                    <p style="font-size: 12px; margin-top: 0.5rem;">Please keep this reference number for your records.</p>
                </div>
                <button class="btn btn-primary" onclick="closeConfirmationModal()">Close</button>
            </div>
        </div>
    </div>

    <script>
        let theatres = <?php echo $theatres_json; ?>;
        let cart = {};
        let selectedTheatreId = null;
        let selectedTheatreName = '';

        function handleTheatreSelect() {
            const theatreSelect = document.getElementById('theatreSelect');
            selectedTheatreId = theatreSelect.value;
            
            if (!selectedTheatreId) {
                document.getElementById('optionsStep').style.display = 'none';
                return;
            }

            // Find theatre name
            const theatre = theatres.find(t => t.t_id == selectedTheatreId);
            selectedTheatreName = theatre ? theatre.theatre_name : '';
            
            document.getElementById('theatreNameDisplay').textContent = selectedTheatreName;
            document.getElementById('optionsStep').style.display = 'block';
            
            // Load orders
            loadMyOrders();
        }

        function openFoodModal() {
            if (!selectedTheatreId) {
                alert('Please select a theatre first');
                return;
            }
            loadFoodItems();
            document.getElementById('foodModal').classList.add('active');
        }

        function closeFoodModal() {
            document.getElementById('foodModal').classList.remove('active');
        }

        function openComplaintModal() {
            if (!selectedTheatreId) {
                alert('Please select a theatre first');
                return;
            }
            document.getElementById('complaintForm').style.display = 'block';
            document.getElementById('complaintNotice').style.display = 'none';
            document.getElementById('complaintModal').classList.add('active');
        }

        function closeComplaintModal() {
            document.getElementById('complaintModal').classList.remove('active');
            document.getElementById('complaintForm').reset();
        }

        function openConfirmationModal(orderRef) {
            document.getElementById('confirmationReference').textContent = orderRef;
            document.getElementById('confirmationModal').classList.add('active');
        }

        function closeConfirmationModal() {
            document.getElementById('confirmationModal').classList.remove('active');
            closeFoodModal();
            loadMyOrders();
        }

        function loadFoodItems() {
            fetch('api_fetch_food_items.php?t_id=' + selectedTheatreId)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success' && data.data.items && data.data.items.length > 0) {
                        renderFoodItems(data.data.items);
                    } else {
                        document.getElementById('foodListContainer').innerHTML = '<p style="color: var(--text-secondary); text-align: center; padding: 2rem;">No food items available at this theatre</p>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('foodListContainer').innerHTML = '<p style="color: #ff6b6b;">Error loading food items</p>';
                });
        }

        function renderFoodItems(items) {
            let html = '<div class="food-grid">';
            
            items.forEach(food => {
                html += `
                    <div class="food-card">
                        
                        <div class="food-name">${escapeHtml(food.food_name)}</div>
                        <div class="food-price">৳${food.price}</div>
                        <div class="food-qty">
                            <button type="button" class="qty-btn" onclick="decreaseQty(${food.food_id})">−</button>
                            <input type="number" class="qty-input" id="qty-${food.food_id}" value="0" min="0" readonly>
                            <button type="button" class="qty-btn" onclick="increaseQty(${food.food_id})">+</button>
                        </div>
                        <button type="button" class="add-btn" onclick="addToCart(${food.food_id}, '${escapeHtml(food.food_name)}', ${food.price})">Add</button>
                    </div>
                `;
            });
            
            html += '</div>';

            html += `
                <div class="order-summary" id="orderSummary" style="display: none; margin-top: 2rem;">
                    <div class="summary-title">Order Summary</div>
                    <div id="summaryItems"></div>
                    <div class="summary-total">
                        <span>Total:</span>
                        <span id="totalPrice">₹0</span>
                    </div>
                    <button type="button" class="order-btn" onclick="submitOrder()">Place Order</button>
                </div>
            `;

            document.getElementById('foodListContainer').innerHTML = html;
        }

        function increaseQty(foodId) {
            const input = document.getElementById(`qty-${foodId}`);
            input.value = Math.max(0, parseInt(input.value || 0) + 1);
        }

        function decreaseQty(foodId) {
            const input = document.getElementById(`qty-${foodId}`);
            input.value = Math.max(0, parseInt(input.value || 0) - 1);
        }

        function addToCart(foodId, foodName, price) {
            const qty = parseInt(document.getElementById(`qty-${foodId}`).value || 0);
            if (qty > 0) {
                if (!cart[foodId]) {
                    cart[foodId] = { name: foodName, price: price, qty: 0 };
                }
                cart[foodId].qty += qty;
                document.getElementById(`qty-${foodId}`).value = 0;
                updateSummary();
            }
        }

        function updateSummary() {
            const items = Object.values(cart).filter(item => item.qty > 0);
            if (items.length === 0) {
                document.getElementById('orderSummary').style.display = 'none';
                return;
            }

            let total = 0;
            let html = '';
            items.forEach((item, idx) => {
                const subtotal = item.qty * item.price;
                total += subtotal;
                html += `
                        <div class="summary-item">
                            <span>${escapeHtml(item.name)} × ${item.qty}</span>
                            <span>৳${subtotal.toLocaleString('en-IN')}</span>
                        </div>
                `;
            });

            document.getElementById('summaryItems').innerHTML = html;
            document.getElementById('totalPrice').textContent = `৳${total.toLocaleString('en-IN')}`;
            document.getElementById('orderSummary').style.display = 'block';
        }

        function submitOrder() {
            const items = Object.entries(cart).filter(([_, item]) => item.qty > 0);
            if (items.length === 0) {
                alert('Please add items to your order');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'place_order');
            formData.append('t_id', selectedTheatreId);
            formData.append('items', JSON.stringify(items.map(([id, item]) => ({
                food_id: id,
                qty: item.qty,
                price: item.price
            }))));

            fetch('food-order-handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const orderId = String(data.data.order_id).padStart(6, '0');
                    const orderRef = `ORDER-${orderId}`;
                    Object.keys(cart).forEach(key => delete cart[key]);
                    updateSummary();
                    openConfirmationModal(orderRef);
                } else if (data.status === 'insufficient_balance') {
                    // Store pending food order and redirect to recharge
                    alert(data.message);
                    const deficit = data.data.deficit;
                    const redirectUrl = `recharge.php?amount=${Math.ceil(deficit)}&return_to=food`;
                    window.location.href = redirectUrl;
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error placing order');
            });
        }

        function submitComplaint(event) {
            event.preventDefault();
            const message = document.querySelector('#complaintForm textarea[name="message"]').value;
            
            const formData = new FormData();
            formData.append('action', 'submit_complaint');
            formData.append('theatre_id', selectedTheatreId);
            formData.append('message', message);

            fetch('complaint-handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('✅ Complaint submitted successfully!');
                    event.target.reset();
                    closeComplaintModal();
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error submitting complaint');
            });
        }

        function loadMyOrders() {
            fetch('api_fetch_user_orders.php?t_id=' + selectedTheatreId)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success' && data.orders.length > 0) {
                        renderOrders(data.orders);
                    } else {
                        document.getElementById('myOrdersList').innerHTML = '<p style="color: var(--text-secondary);">No orders yet at this theatre</p>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('myOrdersList').innerHTML = '<p style="color: var(--text-secondary);">Error loading orders</p>';
                });
        }

        function renderOrders(orders) {
            let html = '<div style="display: grid; gap: 1rem;">';
            
            orders.slice(0, 5).forEach(order => {
                const date = new Date(order.order_date).toLocaleDateString('en-IN');
                const orderId = String(order.order_id).padStart(6, '0');
                html += `
                    <div style="background: var(--secondary-bg); padding: 1rem; border-radius: 8px; border-left: 3px solid var(--accent-red);">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <div style="font-weight: bold; color: var(--text-primary);">ORDER-${orderId}</div>
                                <div style="font-size: 12px; color: var(--text-secondary);">${date}</div>
                            </div>
                            <div style="text-align: right;">
                                    <div style="font-size: 18px; font-weight: bold; color: var(--accent-red);">৳${parseFloat(order.total_price).toLocaleString('en-IN')}</div>
                                <div style="font-size: 12px; color: var(--text-secondary); text-transform: capitalize;">${order.order_status}</div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            document.getElementById('myOrdersList').innerHTML = html;
        }

        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }

        // Close modals on background click
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('active');
            }
        }
    </script>
</body>
</html>