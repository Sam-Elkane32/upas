<x-app-layout>
    <x-slot name="header"><div></div></x-slot>

    <div class="pt-2 pb-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @include('super-admin.partials.page-header', [
                'title' => $activeTab === 'create' ? 'Create Form' : 'Forms Management',
                'subtitle' => $activeTab === 'create' ? 'Set up a new form for a division and strategic goal' : 'Manage forms across all campuses',
            ])

            <!-- Content (server-side tab selection) -->
            @if($activeTab === 'create')
                @include('super-admin.templates.partials.create-form-tab')
            @else
                @include('super-admin.templates.partials.forms-list-tab')
                @if(session('success'))
                    <script>
                        try { sessionStorage.removeItem('uaps_create_form_draft'); } catch (_) {}
                    </script>
                @endif
            @endif
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Scroll to highlighted template if present
            @if(request('highlight'))
                setTimeout(function() {
                    const highlightedRow = document.getElementById('template-row-{{ request('highlight') }}');
                    if (highlightedRow) {
                        highlightedRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        // Remove highlight after 5 seconds
                        setTimeout(function() {
                            highlightedRow.classList.remove('bg-blue-50', 'border-l-4', 'border-blue-500');
                        }, 5000);
                    }
                }, 500);
            @endif

            // Toast notification system
            function showToast(message, type = 'success') {
                const toast = document.createElement('div');
                toast.className = `fixed top-4 right-4 z-50 px-6 py-4 rounded-lg shadow-lg flex items-center space-x-3 animate-slide-in ${
                    type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
                }`;
                toast.innerHTML = `
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        ${type === 'success' 
                            ? '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>'
                            : '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>'
                        }
                    </svg>
                    <span>${message}</span>
                    <button onclick="this.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                `;
                document.body.appendChild(toast);
                
                // Auto remove after 3 seconds
                setTimeout(() => {
                    toast.style.opacity = '0';
                    toast.style.transform = 'translateX(100%)';
                    setTimeout(() => toast.remove(), 300);
                }, 3000);
            }

            // Toggle form status via AJAX
            window.toggleFormStatus = function(formId, currentStatus) {
                const button = event.target.closest('.toggle-status-btn');
                const originalText = button.textContent.trim();
                
                // Disable button during request
                button.disabled = true;
                button.style.opacity = '0.5';
                
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                
                fetch(`/super-admin/forms/${formId}/toggle-status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update button text
                        button.textContent = data.status === 'Published' ? 'Unpublish' : 'Publish';
                        
                        // Update status badge in the table row
                        const statusCell = document.querySelector(`td[data-status-cell="${formId}"]`);
                        if (statusCell) {
                            if (data.status === 'Published') {
                                statusCell.innerHTML = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Published</span>';
                            } else {
                                statusCell.innerHTML = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Unpublished</span>';
                            }
                        }
                        
                        // Show toast notification
                        showToast(data.message, 'success');
                    } else {
                        showToast(data.error || 'Failed to update form status', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Failed to update form status', 'error');
                })
                .finally(() => {
                    // Re-enable button
                    button.disabled = false;
                    button.style.opacity = '1';
                });
            };

            // Delete template function
            window.deleteTemplate = function(templateId, templateCode) {
                window.showConfirm({
                    title: 'Confirm',
                    message: 'Are you sure you want to delete template "' + templateCode + '"? This action cannot be undone.',
                    confirmText: 'Yes, delete',
                    cancelText: 'Cancel',
                    onConfirm: function() {
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        fetch(`/super-admin/templates/${templateId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove the row from the table
                        const row = document.getElementById(`template-row-${templateId}`);
                        if (row) {
                            row.style.transition = 'opacity 0.3s';
                            row.style.opacity = '0';
                            setTimeout(() => {
                                row.remove();
                                // Check if table is empty
                                const tbody = row.closest('tbody');
                                if (tbody && tbody.children.length === 0) {
                                    const tableContainer = tbody.closest('.overflow-x-auto');
                                    if (tableContainer) {
                                        tableContainer.innerHTML = '<div class="p-6 text-center"><p class="text-gray-500">No templates found.</p></div>';
                                    }
                                }
                            }, 300);
                        }
                        showToast(data.message, 'success');
                    } else {
                        showToast(data.message || 'Failed to delete template', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('An error occurred while deleting the template', 'error');
                });
                    }
                });
            };
        });
    </script>
    <style>
        @keyframes slide-in {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        .animate-slide-in {
            animation: slide-in 0.3s ease-out;
        }
    </style>
</x-app-layout>
