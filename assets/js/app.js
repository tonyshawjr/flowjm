/**
 * FlowJM Application JavaScript
 * Core functionality and interactive features
 */

// Application namespace
const FlowJM = {
    // Configuration
    config: {
        apiBaseUrl: '/api/',
        toastDuration: 4000,
        debounceDelay: 300,
        loadingDelay: 500
    },
    
    // State management
    state: {
        user: null,
        campDrawerOpen: false,
        fabMenuOpen: false,
        currentPage: 1,
        loading: false
    },
    
    // Initialize application
    init() {
        this.setupEventListeners();
        this.setupTooltips();
        this.initializeComponents();
        this.checkConnection();
    },
    
    // Setup global event listeners
    setupEventListeners() {
        // Global keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            // Escape key closes modals and drawers
            if (e.key === 'Escape') {
                this.closeAllModals();
            }
            
            // Cmd/Ctrl + K for search
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                this.openSearch();
            }
        });
        
        // Handle offline/online states
        window.addEventListener('online', () => {
            this.showToast('Connection restored', 'success');
            this.checkConnection();
        });
        
        window.addEventListener('offline', () => {
            this.showToast('Connection lost', 'warning');
        });
        
        // Auto-save forms
        document.addEventListener('input', this.debounce((e) => {
            if (e.target.hasAttribute('data-autosave')) {
                this.autoSaveForm(e.target.form);
            }
        }, this.config.debounceDelay));
    },
    
    // Setup tooltips using native browser API where available
    setupTooltips() {
        const elementsWithTooltips = document.querySelectorAll('[title], [data-tooltip]');
        elementsWithTooltips.forEach(element => {
            element.addEventListener('mouseenter', this.showTooltip.bind(this));
            element.addEventListener('mouseleave', this.hideTooltip.bind(this));
        });
    },
    
    // Initialize components
    initializeComponents() {
        // Initialize auto-growing textareas
        this.initAutoGrowTextareas();
        
        // Initialize infinite scroll
        this.initInfiniteScroll();
        
        // Initialize file uploads
        this.initFileUploads();
        
        // Load user preferences
        this.loadUserPreferences();
    },
    
    // Auto-growing textareas
    initAutoGrowTextareas() {
        const textareas = document.querySelectorAll('textarea[data-autogrow]');
        textareas.forEach(textarea => {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
            
            // Initial resize
            textarea.dispatchEvent(new Event('input'));
        });
    },
    
    // Infinite scroll for feeds
    initInfiniteScroll() {
        const scrollContainers = document.querySelectorAll('[data-infinite-scroll]');
        
        scrollContainers.forEach(container => {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting && !this.state.loading) {
                        this.loadMoreContent(container);
                    }
                });
            }, { threshold: 0.1 });
            
            const sentinel = container.querySelector('.scroll-sentinel');
            if (sentinel) {
                observer.observe(sentinel);
            }
        });
    },
    
    // File upload handling
    initFileUploads() {
        const fileInputs = document.querySelectorAll('input[type="file"][data-preview]');
        
        fileInputs.forEach(input => {
            input.addEventListener('change', (e) => {
                this.handleFilePreview(e.target);
            });
        });
        
        // Drag and drop
        const dropZones = document.querySelectorAll('[data-drop-zone]');
        dropZones.forEach(zone => {
            zone.addEventListener('dragover', this.handleDragOver.bind(this));
            zone.addEventListener('drop', this.handleDrop.bind(this));
        });
    },
    
    // Toast notification system
    showToast(message, type = 'info', duration = null) {
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;
        
        // Add close button
        const closeBtn = document.createElement('button');
        closeBtn.innerHTML = '&times;';
        closeBtn.className = 'ml-2 text-white opacity-75 hover:opacity-100';
        closeBtn.onclick = () => this.hideToast(toast);
        toast.appendChild(closeBtn);
        
        document.body.appendChild(toast);
        
        // Show toast
        requestAnimationFrame(() => {
            toast.classList.add('show');
        });
        
        // Auto-hide
        setTimeout(() => {
            this.hideToast(toast);
        }, duration || this.config.toastDuration);
        
        return toast;
    },
    
    hideToast(toast) {
        toast.classList.remove('show');
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    },
    
    // Modal management
    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
            
            // Focus first input
            const firstInput = modal.querySelector('input, textarea, select, button');
            if (firstInput) {
                firstInput.focus();
            }
        }
    },
    
    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = '';
        }
    },
    
    closeAllModals() {
        const modals = document.querySelectorAll('.modal:not(.hidden)');
        modals.forEach(modal => {
            this.closeModal(modal.id);
        });
        
        // Close camp drawer
        if (this.state.campDrawerOpen) {
            window.closeCampDrawer();
        }
    },
    
    // Search functionality
    openSearch() {
        const searchModal = document.getElementById('search-modal');
        if (searchModal) {
            this.openModal('search-modal');
        } else {
            // Fallback to simple search
            const query = prompt('Search FlowJM:');
            if (query) {
                window.location.href = `/search.php?q=${encodeURIComponent(query)}`;
            }
        }
    },
    
    // AJAX helpers
    async request(url, options = {}) {
        const config = {
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            ...options
        };
        
        // Add CSRF token if available
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrfToken) {
            config.headers['X-CSRF-Token'] = csrfToken;
        }
        
        try {
            const response = await fetch(url, config);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Request failed:', error);
            this.showToast('Request failed. Please try again.', 'error');
            throw error;
        }
    },
    
    // Form handling
    async submitForm(form, showLoading = true) {
        const formData = new FormData(form);
        const url = form.action || window.location.href;
        const method = form.method || 'POST';
        
        if (showLoading) {
            this.showLoading();
        }
        
        try {
            const response = await this.request(url, {
                method: method.toUpperCase(),
                body: method.toUpperCase() === 'GET' ? null : formData
            });
            
            if (response.success) {
                this.showToast(response.message || 'Success!', 'success');
                
                // Handle redirects
                if (response.redirect) {
                    window.location.href = response.redirect;
                    return;
                }
                
                // Handle updates
                if (response.update) {
                    this.updateContent(response.update);
                }
            } else {
                this.showToast(response.message || 'An error occurred', 'error');
                this.showFormErrors(form, response.errors || {});
            }
        } catch (error) {
            this.showToast('Failed to submit form. Please try again.', 'error');
        } finally {
            if (showLoading) {
                this.hideLoading();
            }
        }
    },
    
    // Auto-save forms
    autoSaveForm(form) {
        if (!form || this.state.loading) return;
        
        const formData = new FormData(form);
        formData.append('auto_save', '1');
        
        // Visual indicator
        const indicator = form.querySelector('.autosave-indicator');
        if (indicator) {
            indicator.textContent = 'Saving...';
            indicator.className = 'autosave-indicator text-yellow-600';
        }
        
        this.request(form.action, {
            method: 'POST',
            body: formData
        }).then(response => {
            if (indicator) {
                if (response.success) {
                    indicator.textContent = 'Saved';
                    indicator.className = 'autosave-indicator text-green-600';
                } else {
                    indicator.textContent = 'Error saving';
                    indicator.className = 'autosave-indicator text-red-600';
                }
                
                setTimeout(() => {
                    if (indicator) {
                        indicator.textContent = '';
                    }
                }, 2000);
            }
        }).catch(() => {
            if (indicator) {
                indicator.textContent = 'Error saving';
                indicator.className = 'autosave-indicator text-red-600';
            }
        });
    },
    
    // Load more content for infinite scroll
    async loadMoreContent(container) {
        this.state.loading = true;
        const url = container.dataset.loadMoreUrl;
        const nextPage = ++this.state.currentPage;
        
        try {
            const response = await this.request(`${url}?page=${nextPage}`);
            
            if (response.html) {
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = response.html;
                
                while (tempDiv.firstChild) {
                    container.appendChild(tempDiv.firstChild);
                }
                
                // Check if there are more pages
                if (!response.hasMore) {
                    const sentinel = container.querySelector('.scroll-sentinel');
                    if (sentinel) {
                        sentinel.remove();
                    }
                }
            }
        } catch (error) {
            this.showToast('Failed to load more content', 'error');
            this.state.currentPage--; // Revert page increment
        } finally {
            this.state.loading = false;
        }
    },
    
    // Loading states
    showLoading() {
        document.body.classList.add('loading');
        const loader = document.getElementById('global-loader');
        if (loader) {
            loader.classList.remove('hidden');
        }
    },
    
    hideLoading() {
        document.body.classList.remove('loading');
        const loader = document.getElementById('global-loader');
        if (loader) {
            loader.classList.add('hidden');
        }
    },
    
    // Utility functions
    debounce(func, delay) {
        let timeoutId;
        return (...args) => {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => func.apply(this, args), delay);
        };
    },
    
    // Check connection status
    async checkConnection() {
        try {
            await this.request('/api/ping.php');
            document.body.classList.remove('offline');
        } catch (error) {
            document.body.classList.add('offline');
        }
    },
    
    // Load user preferences from localStorage
    loadUserPreferences() {
        const prefs = localStorage.getItem('flowjm-preferences');
        if (prefs) {
            try {
                const preferences = JSON.parse(prefs);
                this.applyPreferences(preferences);
            } catch (error) {
                console.error('Failed to load preferences:', error);
            }
        }
    },
    
    // Apply user preferences
    applyPreferences(preferences) {
        // Theme
        if (preferences.theme) {
            document.body.classList.add(`theme-${preferences.theme}`);
        }
        
        // Font size
        if (preferences.fontSize) {
            document.documentElement.style.fontSize = preferences.fontSize;
        }
        
        // Reduced motion
        if (preferences.reducedMotion) {
            document.body.classList.add('reduced-motion');
        }
    },
    
    // File handling
    handleFilePreview(input) {
        const files = input.files;
        const previewContainer = document.getElementById(`${input.id}-preview`);
        
        if (!previewContainer || !files.length) return;
        
        previewContainer.innerHTML = '';
        
        Array.from(files).forEach(file => {
            const reader = new FileReader();
            reader.onload = (e) => {
                const preview = this.createFilePreview(file, e.target.result);
                previewContainer.appendChild(preview);
            };
            
            if (file.type.startsWith('image/')) {
                reader.readAsDataURL(file);
            } else {
                const preview = this.createFilePreview(file);
                previewContainer.appendChild(preview);
            }
        });
    },
    
    createFilePreview(file, dataUrl = null) {
        const div = document.createElement('div');
        div.className = 'file-preview p-2 border rounded flex items-center space-x-2';
        
        if (dataUrl && file.type.startsWith('image/')) {
            const img = document.createElement('img');
            img.src = dataUrl;
            img.className = 'w-10 h-10 object-cover rounded';
            div.appendChild(img);
        } else {
            const icon = document.createElement('div');
            icon.className = 'w-10 h-10 bg-gray-200 rounded flex items-center justify-center';
            icon.textContent = '📄';
            div.appendChild(icon);
        }
        
        const info = document.createElement('div');
        info.innerHTML = `
            <div class="text-sm font-medium">${file.name}</div>
            <div class="text-xs text-gray-500">${this.formatFileSize(file.size)}</div>
        `;
        div.appendChild(info);
        
        return div;
    },
    
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    },
    
    // Drag and drop
    handleDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'copy';
        e.target.classList.add('drag-over');
    },
    
    handleDrop(e) {
        e.preventDefault();
        e.target.classList.remove('drag-over');
        
        const files = e.dataTransfer.files;
        const fileInput = e.target.querySelector('input[type="file"]');
        
        if (fileInput && files.length) {
            fileInput.files = files;
            fileInput.dispatchEvent(new Event('change', { bubbles: true }));
        }
    },
    
    // Tooltip management
    showTooltip(e) {
        // Implementation would depend on whether using a library or custom solution
    },
    
    hideTooltip(e) {
        // Implementation would depend on whether using a library or custom solution
    },
    
    // Form validation helpers
    showFormErrors(form, errors) {
        // Clear existing errors
        form.querySelectorAll('.error-message').forEach(el => el.remove());
        form.querySelectorAll('.error').forEach(el => el.classList.remove('error'));
        
        // Show new errors
        Object.keys(errors).forEach(fieldName => {
            const field = form.querySelector(`[name="${fieldName}"]`);
            if (field) {
                field.classList.add('error');
                
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-message text-red-600 text-sm mt-1';
                errorDiv.textContent = errors[fieldName];
                
                field.parentNode.insertBefore(errorDiv, field.nextSibling);
            }
        });
    },
    
    // Content updates
    updateContent(updates) {
        Object.keys(updates).forEach(selector => {
            const elements = document.querySelectorAll(selector);
            elements.forEach(el => {
                if (updates[selector].html) {
                    el.innerHTML = updates[selector].html;
                }
                if (updates[selector].text) {
                    el.textContent = updates[selector].text;
                }
                if (updates[selector].attributes) {
                    Object.keys(updates[selector].attributes).forEach(attr => {
                        el.setAttribute(attr, updates[selector].attributes[attr]);
                    });
                }
            });
        });
    },
    
    // Stack Feed Management
    stackFeed: {
        currentPage: 1,
        lastId: 0,
        loading: false,
        hasMore: true,
        
        async load(container = null, reset = false) {
            if (this.loading || !this.hasMore) return;
            
            this.loading = true;
            container = container || document.querySelector('#stack-feed');
            
            if (reset) {
                this.currentPage = 1;
                this.lastId = 0;
                this.hasMore = true;
                if (container) container.innerHTML = '';
            }
            
            try {
                const params = new URLSearchParams({
                    page: this.currentPage,
                    per_page: 10,
                    last_id: this.lastId
                });
                
                const response = await FlowJM.request(`/api/stack.php?${params}`);
                
                if (response.success) {
                    this.renderMoments(response.moments, container);
                    this.currentPage = response.pagination.next_page || this.currentPage;
                    this.lastId = response.pagination.last_id;
                    this.hasMore = response.pagination.has_more;
                }
            } catch (error) {
                FlowJM.showToast('Failed to load stack feed', 'error');
            } finally {
                this.loading = false;
            }
        },
        
        renderMoments(moments, container) {
            if (!container) return;
            moments.forEach(moment => {
                const momentElement = this.createMomentElement(moment);
                container.appendChild(momentElement);
            });
        },
        
        createMomentElement(moment) {
            const div = document.createElement('div');
            div.className = 'moment-card bg-white rounded-lg shadow-sm border border-stone-gray/10 p-4 mb-4';
            div.dataset.momentId = moment.id;
            
            div.innerHTML = `
                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0">
                        <span class="text-2xl">${moment.type_icon}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-sm font-medium text-night-sky truncate">${FlowJM.escapeHtml(moment.title)}</h3>
                            <time class="text-xs text-stone-gray" title="${moment.formatted_date}">
                                ${moment.time_ago}
                            </time>
                        </div>
                        ${moment.journey_title ? `<p class="text-xs text-stone-gray mb-2">🗂️ ${FlowJM.escapeHtml(moment.journey_title)}</p>` : ''}
                        ${moment.content ? `<p class="text-sm text-stone-gray">${FlowJM.escapeHtml(moment.content.substring(0, 150))}${moment.content.length > 150 ? '...' : ''}</p>` : ''}
                        ${moment.amount ? `<p class="text-sm font-medium text-sunrise-orange mt-2">$${parseFloat(moment.amount).toFixed(2)}</p>` : ''}
                    </div>
                </div>
            `;
            
            return div;
        }
    },
    
    // Camp Drawer Management
    campDrawer: {
        isOpen: false,
        
        async toggle() {
            if (this.isOpen) {
                this.close();
            } else {
                await this.open();
            }
        },
        
        async open() {
            if (this.isOpen) return;
            
            // For now, just show the drawer without loading data
            // The drawer content is already rendered server-side
            this.show();
            
            // Optional: Load fresh data
            /*
            try {
                const response = await FlowJM.request('/api/camp.php');
                if (response.success) {
                    this.render(response.data);
                }
            } catch (error) {
                console.error('Camp drawer error:', error);
                // Still show the drawer with existing content
            }
            */
        },
        
        close() {
            const drawer = document.getElementById('camp-drawer');
            const overlay = document.getElementById('camp-drawer-overlay');
            
            if (drawer && overlay) {
                // Start close animation
                drawer.classList.add('translate-x-full');
                overlay.classList.remove('opacity-100');
                overlay.classList.add('opacity-0');
                
                // Hide after animation completes
                setTimeout(() => {
                    drawer.classList.add('hidden');
                    overlay.classList.add('hidden');
                    this.isOpen = false;
                    document.body.style.overflow = '';
                    
                    // Update ARIA state
                    const campButton = document.querySelector('[onclick="toggleCampDrawer()"]');
                    if (campButton) {
                        campButton.setAttribute('aria-expanded', 'false');
                    }
                }, 300);
            }
        },
        
        show() {
            const drawer = document.getElementById('camp-drawer');
            const overlay = document.getElementById('camp-drawer-overlay');
            
            if (drawer && overlay) {
                // Remove hidden class first
                drawer.classList.remove('hidden');
                overlay.classList.remove('hidden');
                
                // Trigger animation after next frame
                requestAnimationFrame(() => {
                    drawer.classList.remove('translate-x-full');
                    overlay.classList.remove('opacity-0');
                    overlay.classList.add('opacity-100');
                    this.isOpen = true;
                    document.body.style.overflow = 'hidden';
                    
                    // Update ARIA state
                    const campButton = document.querySelector('[onclick="toggleCampDrawer()"]');
                    if (campButton) {
                        campButton.setAttribute('aria-expanded', 'true');
                    }
                });
            }
        },
        
        render(data) {
            const drawer = document.getElementById('camp-drawer');
            if (!drawer) return;
            
            const content = drawer.querySelector('.drawer-content');
            if (content) {
                content.innerHTML = this.generateHTML(data);
            }
        },
        
        generateHTML(data) {
            return `
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-night-sky flex items-center">
                            <span class="mr-2">🏕️</span> Camp
                        </h2>
                        <button onclick="closeCampDrawer()" class="text-stone-gray hover:text-night-sky">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <!-- Metrics -->
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div class="bg-canvas rounded-lg p-3 text-center">
                            <div class="text-lg font-bold text-night-sky">${data.metrics.active_journeys}</div>
                            <div class="text-xs text-stone-gray">Active</div>
                        </div>
                        <div class="bg-canvas rounded-lg p-3 text-center">
                            <div class="text-lg font-bold text-night-sky">${data.metrics.circle_journeys}</div>
                            <div class="text-xs text-stone-gray">In Circle</div>
                        </div>
                    </div>
                    
                    ${data.circle_journeys.length > 0 ? `
                    <div class="mb-6">
                        <h3 class="text-sm font-semibold text-night-sky mb-3 flex items-center">
                            <span class="mr-2">🔥</span> Circle
                        </h3>
                        <div class="space-y-2">
                            ${data.circle_journeys.map(journey => `
                            <div class="bg-white rounded-lg p-3 border border-stone-gray/10">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <h4 class="text-sm font-medium text-night-sky">${FlowJM.escapeHtml(journey.title)}</h4>
                                        ${journey.client_name ? `<p class="text-xs text-stone-gray">${FlowJM.escapeHtml(journey.client_name)}</p>` : ''}
                                    </div>
                                    <div class="w-3 h-3 rounded-full bg-${journey.pulse_color}-500"></div>
                                </div>
                            </div>
                            `).join('')}
                        </div>
                    </div>
                    ` : ''}
                    
                    ${data.recent_moments.length > 0 ? `
                    <div>
                        <h3 class="text-sm font-semibold text-night-sky mb-3 flex items-center">
                            <span class="mr-2">📝</span> Recent Activity
                        </h3>
                        <div class="space-y-2">
                            ${data.recent_moments.map(moment => `
                            <div class="text-sm">
                                <span class="mr-1">${moment.type_icon}</span>
                                <span class="text-night-sky">${FlowJM.escapeHtml(moment.title)}</span>
                                <span class="text-stone-gray"> · ${moment.time_ago}</span>
                            </div>
                            `).join('')}
                        </div>
                    </div>
                    ` : ''}
                </div>
            `;
        }
    },
    
    // Quick Actions (FAB Menu)
    quickActions: {
        isOpen: false,
        
        toggle() {
            if (this.isOpen) {
                this.close();
            } else {
                this.open();
            }
        },
        
        open() {
            const menu = document.querySelector('.fab-menu');
            const mainButton = document.getElementById('fab-main');
            const icon = document.getElementById('fab-icon');
            
            if (menu && mainButton && icon) {
                // Remove scale-0 and add scale-100 to show menu
                menu.classList.remove('scale-0', 'opacity-0');
                menu.classList.add('scale-100', 'opacity-100');
                
                // Rotate the plus icon to X
                icon.style.transform = 'rotate(45deg)';
                
                // Change button color to indicate active state
                mainButton.classList.add('bg-red-600');
                mainButton.classList.remove('bg-sunrise-orange');
                mainButton.setAttribute('aria-expanded', 'true');
                
                this.isOpen = true;
                
                // Add click outside to close
                setTimeout(() => {
                    document.addEventListener('click', this.handleClickOutside.bind(this));
                }, 100);
            }
        },
        
        close() {
            const menu = document.querySelector('.fab-menu');
            const mainButton = document.getElementById('fab-main');
            const icon = document.getElementById('fab-icon');
            
            if (menu && mainButton && icon) {
                // Add scale-0 to hide menu
                menu.classList.add('scale-0', 'opacity-0');
                menu.classList.remove('scale-100', 'opacity-100');
                
                // Rotate icon back to plus
                icon.style.transform = 'rotate(0deg)';
                
                // Restore original button color
                mainButton.classList.remove('bg-red-600');
                mainButton.classList.add('bg-sunrise-orange');
                mainButton.setAttribute('aria-expanded', 'false');
                
                this.isOpen = false;
                document.removeEventListener('click', this.handleClickOutside.bind(this));
            }
        },
        
        handleClickOutside(event) {
            const fabContainer = event.target.closest('.fab-menu, #fab-main');
            if (!fabContainer) {
                this.close();
            }
        },
        
        async addMoment() {
            this.close();
            // Implementation for quick moment add
            const content = prompt('Quick moment:');
            if (content) {
                // Get the most recent journey or let user select
                const journeys = await this.getActiveJourneys();
                if (journeys.length > 0) {
                    await FlowJM.moment.create({
                        journey_id: journeys[0].id,
                        content: content,
                        moment_type: 'update'
                    });
                }
            }
        },
        
        async getActiveJourneys() {
            try {
                const response = await FlowJM.request('/api/journeys.php?status=active&limit=5');
                return response.success ? response.data : [];
            } catch (error) {
                return [];
            }
        }
    },
    
    // Journey Management
    journey: {
        async create(data) {
            try {
                const response = await FlowJM.request('/api/journeys.php', {
                    method: 'POST',
                    body: JSON.stringify(data)
                });
                
                if (response.success) {
                    FlowJM.showToast('Journey created successfully', 'success');
                    return response.journey;
                } else {
                    FlowJM.showToast('Failed to create journey', 'error');
                    return null;
                }
            } catch (error) {
                FlowJM.showToast('Failed to create journey', 'error');
                return null;
            }
        },
        
        async update(id, data) {
            try {
                const response = await FlowJM.request(`/api/journeys.php?id=${id}`, {
                    method: 'PUT',
                    body: JSON.stringify(data)
                });
                
                if (response.success) {
                    FlowJM.showToast('Journey updated successfully', 'success');
                    return response.journey;
                } else {
                    FlowJM.showToast('Failed to update journey', 'error');
                    return null;
                }
            } catch (error) {
                FlowJM.showToast('Failed to update journey', 'error');
                return null;
            }
        },
        
        async toggleCircle(id) {
            const journey = document.querySelector(`[data-journey-id="${id}"]`);
            const currentlyInCircle = journey?.dataset.inCircle === '1';
            
            const updated = await this.update(id, { in_circle: !currentlyInCircle });
            if (updated) {
                // Update UI
                if (journey) {
                    journey.dataset.inCircle = updated.in_circle ? '1' : '0';
                    const circleButton = journey.querySelector('.circle-toggle');
                    if (circleButton) {
                        circleButton.textContent = updated.in_circle ? '🔥 In Circle' : '⭕ Add to Circle';
                    }
                }
            }
        }
    },
    
    // Moment Management
    moment: {
        async create(data) {
            try {
                const response = await FlowJM.request('/api/moments.php', {
                    method: 'POST',
                    body: JSON.stringify(data)
                });
                
                if (response.success) {
                    FlowJM.showToast('Moment added successfully', 'success');
                    // Refresh stack feed
                    FlowJM.stackFeed.load(null, true);
                    return response.moment;
                } else {
                    FlowJM.showToast('Failed to add moment', 'error');
                    return null;
                }
            } catch (error) {
                FlowJM.showToast('Failed to add moment', 'error');
                return null;
            }
        }
    },
    
    // Search functionality
    search: {
        currentQuery: '',
        debounceTimer: null,
        
        async perform(query, type = 'all') {
            if (query.length < 2) return;
            
            this.currentQuery = query;
            
            try {
                const params = new URLSearchParams({ q: query, type });
                const response = await FlowJM.request(`/api/search.php?${params}`);
                
                if (response.success) {
                    this.renderResults(response);
                }
            } catch (error) {
                FlowJM.showToast('Search failed', 'error');
            }
        },
        
        renderResults(response) {
            const resultsContainer = document.getElementById('search-results');
            if (!resultsContainer) return;
            
            if (response.results.length === 0) {
                resultsContainer.innerHTML = '<div class="text-center text-stone-gray py-8">No results found</div>';
                return;
            }
            
            resultsContainer.innerHTML = response.results.map(result => {
                return `
                    <div class="search-result border-b border-stone-gray/10 py-3">
                        <div class="flex items-start space-x-3">
                            <span class="text-lg">${this.getResultIcon(result.type)}</span>
                            <div class="flex-1">
                                <h3 class="text-sm font-medium text-night-sky">
                                    <a href="${result.url}" class="hover:text-sunrise-orange">
                                        ${FlowJM.escapeHtml(result.title)}
                                    </a>
                                </h3>
                                ${result.journey_title ? `<p class="text-xs text-stone-gray">🗂️ ${FlowJM.escapeHtml(result.journey_title)}</p>` : ''}
                                ${result.content || result.description ? `<p class="text-sm text-stone-gray mt-1">${FlowJM.escapeHtml((result.content || result.description).substring(0, 120))}...</p>` : ''}
                                <div class="flex items-center justify-between mt-2">
                                    <span class="inline-block px-2 py-1 text-xs bg-stone-gray/10 text-stone-gray rounded">
                                        ${result.type}
                                    </span>
                                    <span class="text-xs text-stone-gray">
                                        ${result.time_ago || FlowJM.formatDate(result.created_at)}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        },
        
        getResultIcon(type) {
            const icons = {
                journey: '🗂️',
                moment: '📝',
                fieldnote: '📄'
            };
            return icons[type] || '📄';
        }
    },
    
    // Utility Methods
    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },
    
    formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString();
    }
};

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => FlowJM.init());
} else {
    FlowJM.init();
}

// Global Functions for HTML onclick handlers
window.toggleCampDrawer = () => FlowJM.campDrawer.toggle();
window.closeCampDrawer = () => FlowJM.campDrawer.close();
window.toggleQuickActions = () => FlowJM.quickActions.toggle();
window.addToCircle = (journeyId) => FlowJM.journey.toggleCircle(journeyId);
window.openAddMomentModal = () => FlowJM.quickActions.addMoment();
window.openAddFieldnoteModal = () => FlowJM.showToast('Fieldnote feature coming soon', 'info');

// Menu toggles with ARIA support
window.toggleUserMenu = () => {
    const menu = document.getElementById('user-menu');
    const button = document.getElementById('user-menu-button');
    if (menu && button) {
        const isHidden = menu.classList.toggle('hidden');
        button.setAttribute('aria-expanded', !isHidden);
    }
};

window.toggleMobileMenu = () => {
    const menu = document.getElementById('mobile-menu');
    const button = document.querySelector('[onclick="toggleMobileMenu()"]');
    if (menu && button) {
        const isHidden = menu.classList.toggle('hidden');
        button.setAttribute('aria-expanded', !isHidden);
    }
};

// Export for use in other scripts
window.FlowJM = FlowJM;