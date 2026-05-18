/**
 * CSRF Token Interceptor for Axios
 * Handles 419 errors gracefully by refreshing the CSRF token
 */

import axios from 'axios';

// Store original request config for retry
let isRefreshing = false;
let failedQueue = [];

const processQueue = (error, token = null) => {
    failedQueue.forEach(prom => {
        if (error) {
            prom.reject(error);
        } else {
            prom.resolve(token);
        }
    });
    
    failedQueue = [];
};

// Request interceptor to add CSRF token
axios.interceptors.request.use(
    (config) => {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (token) {
            config.headers['X-CSRF-TOKEN'] = token;
        }
        return config;
    },
    (error) => {
        return Promise.reject(error);
    }
);

// Response interceptor to handle 419 errors
axios.interceptors.response.use(
    (response) => {
        // Update CSRF token from response header if available
        const newToken = response.headers['x-csrf-token'];
        if (newToken) {
            const metaTag = document.querySelector('meta[name="csrf-token"]');
            if (metaTag) {
                metaTag.setAttribute('content', newToken);
            }
            // Update all hidden CSRF input fields
            document.querySelectorAll('input[name="_token"]').forEach(input => {
                input.value = newToken;
            });
        }
        return response;
    },
    async (error) => {
        const originalRequest = error.config;

        // Handle 419 CSRF token expired
        if (error.response?.status === 419 && !originalRequest._retry) {
            if (isRefreshing) {
                // If already refreshing, queue this request
                return new Promise((resolve, reject) => {
                    failedQueue.push({ resolve, reject });
                })
                    .then(token => {
                        originalRequest.headers['X-CSRF-TOKEN'] = token;
                        return axios(originalRequest);
                    })
                    .catch(err => {
                        return Promise.reject(err);
                    });
            }

            originalRequest._retry = true;
            isRefreshing = true;

            try {
                // Refresh CSRF token
                const response = await axios.post('/session/keepalive');
                const newToken = response.data.csrf_token;

                // Update meta tag
                const metaTag = document.querySelector('meta[name="csrf-token"]');
                if (metaTag) {
                    metaTag.setAttribute('content', newToken);
                }

                // Update all hidden CSRF input fields
                document.querySelectorAll('input[name="_token"]').forEach(input => {
                    input.value = newToken;
                });

                // Update original request header
                originalRequest.headers['X-CSRF-TOKEN'] = newToken;

                processQueue(null, newToken);

                // Retry original request
                return axios(originalRequest);
            } catch (refreshError) {
                processQueue(refreshError, null);
                
                // Show non-blocking modal
                showCsrfErrorModal();
                
                return Promise.reject(refreshError);
            } finally {
                isRefreshing = false;
            }
        }

        return Promise.reject(error);
    }
);

/**
 * Show non-blocking CSRF error modal
 */
function showCsrfErrorModal() {
    // Check if modal already exists
    let modal = document.getElementById('csrf-error-modal');
    if (modal) {
        return;
    }

    // Create modal
    modal = document.createElement('div');
    modal.id = 'csrf-error-modal';
    modal.className = 'fixed bottom-4 right-4 bg-yellow-50 border border-yellow-200 rounded-lg shadow-lg p-4 max-w-md z-50';
    modal.innerHTML = `
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="ml-3 flex-1">
                <h3 class="text-sm font-medium text-yellow-800">Session Expired</h3>
                <p class="mt-1 text-sm text-yellow-700">Your session has expired. Please refresh the page or try your action again.</p>
                <div class="mt-3">
                    <button onclick="location.reload()" class="text-sm font-medium text-yellow-800 hover:text-yellow-900 underline">
                        Refresh Page
                    </button>
                </div>
            </div>
            <div class="ml-4 flex-shrink-0">
                <button onclick="this.closest('#csrf-error-modal').remove()" class="text-yellow-400 hover:text-yellow-500">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    // Auto-remove after 10 seconds
    setTimeout(() => {
        if (modal && modal.parentNode) {
            modal.remove();
        }
    }, 10000);
}

export default axios;

