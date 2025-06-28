<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Design Your Own Jewelry</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* --- General Layout --- */
        body, html {
            height: 100%;
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f0f2f5;
            overflow: hidden; /* Prevent the entire page from scrolling */
        }
        .design-page-container {
            display: flex;
            height: 100vh; /* Use 100vh for full viewport height */
            padding-top: 70px; /* Account for the fixed top bar */
        }

        /* --- Top Bar --- */
        .top-bar {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 30px;
            background-color: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            position: fixed; /* Keep the top bar fixed at the top */
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
        }
        .top-bar h1 {
            margin: 0;
            color: #d4a017;
            font-size: 1.8em;
            font-weight: bold;
            text-shadow: 1px 1px 2px rgba(255,255,255,0.8);
            font-family: 'Georgia', serif;
        }
        .inquire-btn {
            background-color: #28a745;
            color: white;
            padding: 10px 25px;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .inquire-btn:hover {
            background-color: #218838;
        }
        /* --- Controls Panel in Top Bar --- */
        #controls-panel {
            display: none; /* Hidden by default */
            align-items: center;
            gap: 15px;
            padding: 0 20px;
            border-left: 1px solid #eee;
            margin-left: 20px;
        }
        #controls-panel .control-group {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        #controls-panel label {
            margin-bottom: 0;
            font-weight: bold;
            color: #555;
        }
        #controls-panel button {
            border-radius: 8px;
        }
        #controls-panel input[type="range"] {
            width: 120px;
        }

        /* --- Sidebar (Component Library) --- */
        .sidebar {
            width: 280px;
            background-color: #fff;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            padding: 20px;
            overflow-y: auto; /* Enable vertical scrolling for the sidebar content */
            flex-shrink: 0; /* Prevent the sidebar from shrinking */
            height: 100%;
        }
        .sidebar h3, .sidebar h4 {
            color: #d4a017;
            border-bottom: 2px solid #eee;
            padding-bottom: 5px;
            margin-top: 20px;
            margin-bottom: 15px;
        }
        .base-items-heading {
            margin-top: 40px; 
            padding-bottom: 5px;
            border-bottom: 2px solid #eee;
            color: #d4a017;
        }
        .sidebar h3:nth-of-type(2) {
            margin-top: 30px; 
        }
        .sidebar-item {
            cursor: pointer;
            padding: 10px;
            margin-bottom: 10px;
            border: 2px solid transparent;
            border-radius: 8px;
            transition: all 0.2s;
            text-align: center;
        }
        .sidebar-item:hover {
            background-color: #f0f0f0;
        }
        .sidebar-item.draggable-element {
            cursor: grab;
        }
        .sidebar-item img {
            max-width: 100px;
            height: auto;
        }
        .sidebar-item p {
            margin: 5px 0 0 0;
            font-size: 0.9em;
            color: #555;
        }
        .color-swatch {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin: auto;
            border: 2px solid #ccc;
        }
        .layer-controls button {
            width: 100%;
            margin-bottom: 10px;
        }
        
        /* --- Main Workspace (Canvas) --- */
        .main-workspace {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center; /* Center the canvas vertically */
            padding: 20px;
            overflow-y: hidden; /* Prevent the main workspace from scrolling */
        }
        .canvas-container {
            position: relative;
            width: 600px; /* Fixed size for the canvas area */
            height: 600px; /* Added a fixed height for better layout control */
            background-color: #ffffff;
            border: 2px dashed #ccc;
            border-radius: 15px;
            overflow: hidden; /* Keep components within the canvas */
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
            background-size: fixed;
            touch-action: none; /* Prevent browser scrolling on drag */
        }
        .design-element {
            position: absolute;
            cursor: move;
            user-select: none;
            width: 100px;
            height: auto;
            z-index: 1; /* Base z-index */
            box-sizing: border-box;
        }
        .design-element.active {
            outline: 2px dashed #007bff;
            outline-offset: 5px;
        }
        .design-element p {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            text-shadow: 1px 1px 2px rgba(255,255,255,0.8);
            margin: 0;
            user-select: text; /* Allow text selection */
        }
        /* --- Responsive Design --- */
        @media (max-width: 768px) {
            .design-page-container {
                flex-direction: column;
                padding-top: 130px; /* Adjust for stacked top bar */
                height: auto; /* Allow content to grow */
            }
            body, html {
                overflow-y: auto; /* Allows the page to scroll on mobile if content exceeds viewport */
            }
            .sidebar {
                width: 100%;
                max-height: 300px;
                order: 2; /* Put sidebar below canvas on mobile */
                height: auto;
            }
            .main-workspace {
                order: 1;
            }
            .canvas-container {
                width: 95%;
                height: 95vw; /* Make canvas responsive square */
                max-height: 500px;
            }
            .top-bar {
                flex-direction: column;
                gap: 10px;
                padding: 10px;
            }
            .top-bar h1 {
                font-size: 1.5em;
            }
            /* NEW: Adjust controls panel for mobile */
            #controls-panel {
                flex-direction: column;
                align-items: stretch;
                width: 100%;
                border-left: none;
                margin-left: 0;
                padding: 10px 0;
                border-top: 1px solid #eee;
            }
        }
    </style>
</head>
<body>
    
    <div class="top-bar">
       <a href="index.html" style="text-decoration: none;"> <h1>Jewellery Designer</h1></a>
        <div id="controls-panel">
            <div class="control-group">
                <label for="size-slider">Size:</label>
                <input type="range" id="size-slider" min="50" max="400" value="100">
            </div>
            <button class="btn btn-danger btn-sm" id="delete-element-btn">
                <i class="fas fa-trash"></i> Delete
            </button>
        </div>
        <div>
            <span class="fw-bold me-3">Estimated Price: <span id="design-price">₹0</span></span>
            <button class="inquire-btn" id="inquire-button">Inquire about this Design</button>
        </div>
    </div>

    <div class="design-page-container">
        <aside class="sidebar">
            <hr>
            <h3 class="base-items-heading">Base Items (Drag one to start)</h3>
            
            <h4>Rings</h4>
            <div class="sidebar-item draggable-element" data-type="ring-base" data-price="1200" data-src="images/images (1).jpeg">
                <img src="images/images (1).jpeg" alt="Classic Ring">
                <p>Ring</p>
            </div>
            <div class="sidebar-item draggable-element" data-type="ring-base" data-price="1500" data-src="images/il_570xN.1645111984_imh5.jpg">
                <img src="images/il_570xN.1645111984_imh5.jpg" alt="Split Shank Ring">
                <p>Split Shank Ring</p>
            </div>
            
            <h4>Necklaces & Sets</h4>
            <div class="sidebar-item draggable-element" data-type="necklace-set" data-price="3500" data-src="images/base pendent.webp">
                <img src="images/base pendent.webp" alt="Pendant & Earrings Set">
                <p>Pendant</p>
            </div>
    
            <hr>
            <h3>Material & Finish</h3>
            <p><small>Drag one of these onto your selected base item.</small></p>
            <div class="sidebar-item draggable-element" data-type="material" data-price-modifier="1" data-label="Yellow Gold">
                <div class="color-swatch" style="background-color: #ffd700;"></div>
                <p>Gold</p>
            </div>
            <div class="sidebar-item draggable-element" data-type="material" data-price-modifier="1.2" data-label="White Gold">
                <div class="color-swatch" style="background-color: #e6e6e6;"></div>
                <p>Silver</p>
            </div>
            <div class="sidebar-item draggable-element" data-type="material" data-price-modifier="1.1" data-label="Rose Gold">
                <div class="color-swatch" style="background-color: #ff6666;"></div>
                <p>Rose Gold</p>
            </div>

            <hr>
            <h3>Add-ons (Stones)</h3>
            <div class="sidebar-item draggable-element" data-type="stone" data-price="250" data-src="images/S54d0f315b1594d358602d0da7536a7beV_e4fdeb39-c403-485f-a704-40b321264727.png">
                <img src="images/S54d0f315b1594d358602d0da7536a7beV_e4fdeb39-c403-485f-a704-40b321264727.png" alt="Diamond">
                <p>Classic Diamond</p>
            </div>
            <div class="sidebar-item draggable-element" data-type="stone" data-price="180" data-src="images/ruby-white-background-transparent-background-ai-generative-png.webp">
                <img src="images/ruby-white-background-transparent-background-ai-generative-png.webp" alt="Ruby">
                <p>Ruby</p>
            </div>
            <div class="sidebar-item draggable-element" data-type="stone" data-price="350" data-src="images/round-blue-sapphire-500x500.png">
                <img src="images/round-blue-sapphire-500x500.png" alt="Blue Sapphire">
                <p>Blue Sapphire</p>
            </div>
            <div class="sidebar-item draggable-element" data-type="stone" data-price="380" data-src="images/Emerald_Single_a7117b973c.png">
                <img src="images/Emerald_Single_a7117b973c.png" alt="Emerald Cut">
                <p>Emerald Cut</p>
            </div>
            <div class="sidebar-item draggable-element" data-type="stone" data-price="280" data-src="images/3-09VI1-Oval-Pink-Spinel-Brian-Gavin.png">
                <img src="images/3-09VI1-Oval-Pink-Spinel-Brian-Gavin.png" alt="Pink Stone">
                <p>Pink Stone</p>
            </div>
            
            <hr>
            <h3>Engraving</h3>
            <div class="sidebar-item draggable-element" data-type="text" data-price="100">
                <i class="fas fa-font fa-3x" style="color: #d4a017;"></i>
                <p>Engraving Text</p>
            </div>

        </aside>

        <main class="main-workspace pt-5 ">
            <div class="canvas-container" id="design-canvas">
            </div>
        </main>
    </div>

    <div class="modal fade" id="inquiryModal" tabindex="-1" aria-labelledby="inquiryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="inquiryModalLabel">Inquire about your Custom Design</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Please provide your details, and we will get back to you with a personalized quote for your unique design.</p>
                    <form id="inquiryForm">
                        <div class="mb-3">
                            <label for="inquiryName" class="form-label">Name</label>
                            <input type="text" class="form-control" id="inquiryName" required>
                        </div>
                        <div class="mb-3">
                            <label for="inquiryEmail" class="form-label">Email address</label>
                            <input type="email" class="form-control" id="inquiryEmail" required>
                        </div>
                        <div class="mb-3">
                            <label for="inquiryMessage" class="form-label">Message (optional)</label>
                            <textarea class="form-control" id="inquiryMessage" rows="3"></textarea>
                        </div>
                        <input type="hidden" id="designDataInput" name="designData">
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Submit Inquiry</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sidebarItems = document.querySelectorAll('.draggable-element');
            const designCanvas = document.getElementById('design-canvas');
            const priceDisplay = document.getElementById('design-price');
            const inquireButton = document.getElementById('inquire-button');

            // NEW: Get the new controls from the top bar
            const controlsPanel = document.getElementById('controls-panel');
            const deleteBtn = document.getElementById('delete-element-btn');
            const sizeSlider = document.getElementById('size-slider');
            
            // NEW: Get the modal and form elements
            const inquiryModalElement = document.getElementById('inquiryModal');
            let inquiryModal = null;
            if (inquiryModalElement) {
                inquiryModal = new bootstrap.Modal(inquiryModalElement);
            } else {
                console.error("Inquiry modal element not found!");
            }
            
            const inquiryForm = document.getElementById('inquiryForm');
            const designDataInput = document.getElementById('designDataInput');

            let currentPrice = 0;
            let selectedElement = null; // Track the currently selected element
            
            // Define all types that can be considered a 'base' item
            const baseItemTypes = ['ring-base', 'necklace-base', 'necklace-set', 'bracelet-base', 'bangle-base', 'nose-pin', 'headpiece', 'anklets', 'chain-base'];

            // --- Price Calculation & UI Update ---
            function updatePrice() {
                const elements = designCanvas.querySelectorAll('.design-element');
                currentPrice = 0;
                let materialModifier = 1;
                let basePrice = 0;

                // Find the primary base item on the canvas
                const mainBase = designCanvas.querySelector('.design-element[data-is-base="true"]');
                if (mainBase) {
                    basePrice = parseFloat(mainBase.dataset.price || 0);
                    materialModifier = parseFloat(mainBase.dataset.priceModifier || 1);
                }
                
                // Sum up all prices of all elements
                elements.forEach(el => {
                    // Only add prices for non-material elements. Material price is a modifier.
                    if (el.dataset.type !== 'material') {
                         currentPrice += parseFloat(el.dataset.price || 0);
                    }
                });
                
                // Adjust the price of the base item with the material modifier.
                currentPrice = currentPrice - basePrice + (basePrice * materialModifier);

                priceDisplay.textContent = `₹${Math.round(currentPrice).toLocaleString()}`;
            }

            // --- Drag and Drop Logic for Sidebar Items ---
            sidebarItems.forEach(item => {
                item.addEventListener('dragstart', (e) => {
                    e.dataTransfer.setData('text/plain', JSON.stringify({
                        type: item.dataset.type,
                        src: item.dataset.src || '',
                        price: item.dataset.price || 0,
                        priceModifier: item.dataset.priceModifier || 1,
                        label: item.dataset.label || '',
                        isBaseItem: baseItemTypes.includes(item.dataset.type)
                    }));
                });
                item.setAttribute('draggable', true);
            });

            designCanvas.addEventListener('dragover', (e) => {
                e.preventDefault(); // Allow drop
            });

            designCanvas.addEventListener('drop', (e) => {
                e.preventDefault();
                const data = JSON.parse(e.dataTransfer.getData('text/plain'));
                
                const canvasRect = designCanvas.getBoundingClientRect();
                let x = e.clientX - canvasRect.left;
                let y = e.clientY - canvasRect.top;

                // --- NEW: Handle Material/Color change directly on the base item ---
                if (data.type === 'material') {
                    const baseElement = designCanvas.querySelector('.design-element[data-is-base="true"]');
                    if (baseElement) {
                        baseElement.dataset.priceModifier = data.priceModifier;
                        baseElement.dataset.materialLabel = data.label;
                        
                        // NEW: A simple way to change the image source based on the selected material
                        // This requires you to have corresponding images like "ring_gold.jpeg", "ring_silver.jpeg", etc.
                        const currentSrc = baseElement.src;
                        const newSrc = currentSrc.replace(/(_(gold|silver|rose-gold))?\.([a-z]+)$/, `_${data.label.toLowerCase().replace(' ', '-')}.$3`);
                        baseElement.src = newSrc;
                        
                        updatePrice();
                    }
                    return; // Don't add a new element to the canvas
                }

                // If dropping a new base item, remove the old one first
                if (data.isBaseItem) {
                    const existingBase = designCanvas.querySelector('.design-element[data-is-base="true"]');
                    if (existingBase) {
                        existingBase.remove();
                    }
                }

                let newElement;

                // Handle Engraving Text
                if (data.type === 'text') {
                    const engravingText = prompt("Enter the text for engraving:");
                    if (!engravingText) return; // Exit if user cancels
                    
                    newElement = document.createElement('div');
                    newElement.innerHTML = `<p>${engravingText}</p>`;
                    newElement.style.cssText = `
                        position: absolute;
                        font-family: Arial, sans-serif;
                        font-size: 24px;
                        font-weight: bold;
                        color: #333;
                        text-shadow: 1px 1px 2px rgba(255,255,255,0.8);
                        white-space: nowrap;
                    `;
                    newElement.dataset.price = data.price;
                    newElement.dataset.type = data.type;
                    newElement.classList.add('design-element'); // NEW: Add the class for selection and dragging
                } else { // Handle Image elements (base, stone, etc.)
                    newElement = document.createElement('img');
                    newElement.src = data.src;
                    newElement.alt = data.type;
                    newElement.classList.add('design-element');
                    newElement.dataset.price = data.price;
                    newElement.dataset.type = data.type;
                    
                    // Set a flag for base items to be used in price calculation
                    if (data.isBaseItem) {
                        newElement.dataset.isBaseItem = 'true';
                        // Adjust size based on jewelry type
                        if (['ring-base', 'nose-pin'].includes(data.type)) {
                            newElement.style.width = '250px';
                        } else if (['necklace-base', 'necklace-set', 'headpiece', 'chain-base'].includes(data.type)) {
                            newElement.style.width = '350px';
                        } else if (['bracelet-base', 'bangle-base', 'anklets'].includes(data.type)) {
                            newElement.style.width = '300px';
                        }
                    } else {
                        newElement.style.width = '50px'; // Make stones smaller
                    }
                    newElement.style.height = 'auto';
                }
                
                // Position the new element, centered on the cursor
                newElement.style.left = `${x}px`;
                newElement.style.top = `${y}px`;
                newElement.style.transform = `translate(-50%, -50%)`; // Keep centering on drop
                
                // Make the element draggable inside the canvas
                makeDraggable(newElement);

                designCanvas.appendChild(newElement);
                updatePrice();
            });

            // --- Function to make an element draggable and selectable ---
            function makeDraggable(element) {
                let isDragging = false;
                let dragStarted = false;
                let startX, startY;
                let initialX, initialY;
                let dragThreshold = 5; // Pixels the mouse must move to be considered a drag

                // Use a mousedown event to initiate both selection and dragging
                element.addEventListener('mousedown', (e) => {
                    if (e.button !== 0) return;
                    e.preventDefault();
                    isDragging = false;
                    dragStarted = false;
                    element.style.cursor = 'move';

                    startX = e.clientX;
                    startY = e.clientY;
                    initialX = element.offsetLeft;
                    initialY = element.offsetTop;

                    // Attach temporary listeners for the drag operation
                    document.addEventListener('mousemove', onMouseMove);
                    document.addEventListener('mouseup', onMouseUp);
                });

                function onMouseMove(e) {
                    if (!dragStarted) {
                        const deltaX = e.clientX - startX;
                        const deltaY = e.clientY - startY;
                        // If the mouse moves more than the threshold, start dragging
                        if (Math.abs(deltaX) > dragThreshold || Math.abs(deltaY) > dragThreshold) {
                            dragStarted = true;
                            isDragging = true;
                            element.style.cursor = 'grabbing';
                            // Select the element only when a drag starts, not on a simple click
                            selectElement(element); 
                        }
                    }

                    if (isDragging) {
                        const canvasRect = designCanvas.getBoundingClientRect();
                        
                        let deltaX = e.clientX - startX;
                        let deltaY = e.clientY - startY;

                        let newX = initialX + deltaX;
                        let newY = initialY + deltaY;

                        // Clamp position within canvas boundaries
                        newX = Math.max(0, Math.min(newX, canvasRect.width - element.offsetWidth));
                        newY = Math.max(0, Math.min(newY, canvasRect.height - element.offsetHeight));

                        element.style.left = `${newX}px`;
                        element.style.top = `${newY}px`;
                    }
                }

                function onMouseUp(e) {
                    if (!dragStarted) {
                        // If drag didn't start, it was a click, so select the element
                        selectElement(element);
                    }
                    isDragging = false;
                    dragStarted = false;
                    element.style.cursor = 'move';
                    // Remove the temporary listeners
                    document.removeEventListener('mousemove', onMouseMove);
                    document.removeEventListener('mouseup', onMouseUp);
                }
                
                // Set initial cursor style
                element.style.cursor = 'move';
                element.style.userSelect = 'none';
            }

            // --- Element Selection & Control Panel Logic ---
            function selectElement(element) {
                // Deselect previous element
                if (selectedElement && selectedElement !== element) {
                    selectedElement.classList.remove('active');
                }
                // Select new element
                selectedElement = element;
                selectedElement.classList.add('active');
                
                // Show controls panel and set slider value
                controlsPanel.style.display = 'flex';
                // Set the slider to the current width of the element
                sizeSlider.value = parseInt(selectedElement.style.width, 10) || 100;
            }

            // Deselect element if canvas background is clicked
            designCanvas.addEventListener('click', (e) => {
                if (e.target.id === 'design-canvas' && selectedElement) {
                    selectedElement.classList.remove('active');
                    selectedElement = null;
                    controlsPanel.style.display = 'none'; // Hide controls when nothing is selected
                }
            });
            
            // --- NEW: Delete button logic ---
            deleteBtn.addEventListener('click', () => {
                if (selectedElement) {
                    selectedElement.remove();
                    selectedElement = null; // Deselect the element after deleting
                    controlsPanel.style.display = 'none'; // Hide controls
                    updatePrice(); // Recalculate price
                }
            });
            
            // --- NEW: Resizing slider logic ---
            sizeSlider.addEventListener('input', (e) => {
                if (selectedElement) {
                    const newSize = `${e.target.value}px`;
                    selectedElement.style.width = newSize;
                    selectedElement.style.height = 'auto'; // Maintain aspect ratio
                }
            });

            // --- Inquiry Button Logic ---
inquireButton.addEventListener('click', () => {
    // A more robust way to check if a base item exists on the canvas
    const baseItems = designCanvas.querySelectorAll('.design-element');
    let baseItemFound = false;

    // Check if any element on the canvas has the 'data-is-base' attribute set to 'true'
    baseItems.forEach(el => {
        if (el.dataset.isBaseItem === 'true') {
            baseItemFound = true;
        }
    });

    console.log(`Checking for base item. Found: ${baseItemFound}`);
    
    if (!baseItemFound) {
        alert('Please drag a base item (like a ring or necklace) onto the canvas to start your design before inquiring.');
        return;
    }

    // Capture the design data (this part is the same as before)
    const designElements = [];
    let baseMaterial = 'Not Selected';
    let primaryBaseItem = 'None';

    designCanvas.querySelectorAll('.design-element').forEach(el => {
        if (el.dataset.isBaseItem === 'true') {
            primaryBaseItem = el.alt || el.dataset.type;
            baseMaterial = el.dataset.materialLabel || 'Yellow Gold (Default)';
        }
        
        designElements.push({
            type: el.dataset.type,
            source: el.tagName === 'IMG' ? el.src : 'User Input Text',
            content: el.tagName === 'DIV' ? el.textContent : null,
            price: el.dataset.price,
            position: {
                x: el.style.left,
                y: el.style.top
            },
            zIndex: el.style.zIndex || 1
        });
    });
    
    const finalDesign = {
        totalPrice: currentPrice,
        baseItem: primaryBaseItem,
        material: baseMaterial,
        components: designElements,
    };
    
    // Set the value of the hidden input and show the modal
    designDataInput.value = JSON.stringify(finalDesign);
    
    if (inquiryModal) {
        inquiryModal.show();
    }
});

            // --- Form Submission Logic ---
            inquiryForm.addEventListener('submit', (e) => {
                e.preventDefault();
                
                const name = document.getElementById('inquiryName').value;
                const email = document.getElementById('inquiryEmail').value;
                const message = document.getElementById('inquiryMessage').value;
                const designData = document.getElementById('designDataInput').value;

                console.log('--- Form Submitted! ---');
                console.log('Name:', name);
                console.log('Email:', email);
                console.log('Message:', message);
                console.log('Design Data:', JSON.parse(designData));

                alert('Thank you for your inquiry! We will contact you soon.');
                
                if (inquiryModal) {
                    inquiryModal.hide();
                }
            });
        });
    </script>
</body>
</html>