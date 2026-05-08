// Admin Dashboard JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Setup faculty/department cascading
    setupFacultyCascade();
    
    // Setup real-time search
    setupSearch();
});

function setupFacultyCascade() {
    const facultySelect = document.getElementById('facultyFilter');
    const departmentSelect = document.getElementById('departmentFilter');
    
    if (!facultySelect || !departmentSelect) return;
    
    facultySelect.addEventListener('change', function() {
        const selectedFaculty = this.value;
        departmentSelect.innerHTML = '<option value="">All Departments</option>';
        
        if (selectedFaculty && window.tsuData) {
            const faculty = window.tsuData.find(f => f.faculty === selectedFaculty);
            if (faculty) {
                faculty.departments.forEach(dept => {
                    const option = document.createElement('option');
                    option.value = dept.name;
                    option.textContent = dept.name;
                    departmentSelect.appendChild(option);
                });
            }
        }
    });
}

function setupSearch() {
    const searchInput = document.getElementById('searchInput');
    if (!searchInput) return;
    
    let searchTimeout;
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            filterTable();
        }, 300);
    });
}

function filterTable() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const rows = document.querySelectorAll('#studentsTableBody tr');
    let visibleCount = 0;
    
    rows.forEach(row => {
        if (row.querySelector('.empty-state')) return;
        
        const text = row.textContent.toLowerCase();
        if (text.includes(searchTerm)) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    document.getElementById('resultsCount').textContent = `${visibleCount} Students`;
}

// Modal Functions
function openNoteModal(studentId, currentNote) {
    const modal = createModal('Edit Student Note', `
        <form id="noteForm" onsubmit="saveNote(event, ${studentId})">
            <div class="form-group">
                <label class="form-label">Admin Note/Message</label>
                <textarea class="form-control" name="note" rows="5" placeholder="Enter any notes or messages for the student...">${currentNote || ''}</textarea>
                <p style="font-size: 0.875rem; color: var(--gray-600); margin-top: 0.5rem;">This message will be visible to the student on their dashboard.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Note</button>
            </div>
        </form>
    `);
    
    document.getElementById('modalContainer').innerHTML = modal;
}

function openPasswordModal(studentId) {
    const modal = createModal('Change Student Password', `
        <form id="passwordForm" onsubmit="savePassword(event, ${studentId})">
            <div class="form-group">
                <label class="form-label">New Password</label>
                <input type="text" class="form-control" name="password" required placeholder="Enter new password" minlength="4">
                <p style="font-size: 0.875rem; color: var(--gray-600); margin-top: 0.5rem;">The student will use this password to login.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Password</button>
            </div>
        </form>
    `);
    
    document.getElementById('modalContainer').innerHTML = modal;
}

function confirmDelete(studentId, regNumber) {
    const modal = createModal('Confirm Deletion', `
        <div style="padding: 1rem 0;">
            <p style="font-size: 1rem; color: var(--gray-700); margin-bottom: 1rem;">
                Are you sure you want to delete student <strong>${regNumber}</strong>?
            </p>
            <p style="font-size: 0.875rem; color: var(--error);">
                This action cannot be undone. All student data will be permanently removed.
            </p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
            <button type="button" class="btn btn-danger" onclick="deleteStudent(${studentId})">Delete Student</button>
        </div>
    `);
    
    document.getElementById('modalContainer').innerHTML = modal;
}

function createModal(title, content) {
    return `
        <div class="modal-overlay" onclick="closeModalOnOverlay(event)">
            <div class="modal" onclick="event.stopPropagation()">
                <div class="modal-header">
                    <h3 style="margin: 0; font-size: 1.25rem; color: var(--gray-900);">${title}</h3>
                </div>
                <div class="modal-body">
                    ${content}
                </div>
            </div>
        </div>
    `;
}

function closeModal() {
    document.getElementById('modalContainer').innerHTML = '';
}

function closeModalOnOverlay(event) {
    if (event.target.classList.contains('modal-overlay')) {
        closeModal();
    }
}

// AJAX Functions
async function saveNote(event, studentId) {
    event.preventDefault();
    const form = event.target;
    const note = form.note.value;
    
    try {
        const response = await fetch('ajax-handlers.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=update_note&student_id=${studentId}&note=${encodeURIComponent(note)}`
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Note saved successfully', 'success');
            closeModal();
        } else {
            showNotification(result.error || 'Failed to save note', 'error');
        }
    } catch (error) {
        showNotification('An error occurred', 'error');
    }
}

async function savePassword(event, studentId) {
    event.preventDefault();
    const form = event.target;
    const password = form.password.value;
    
    try {
        const response = await fetch('ajax-handlers.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=change_password&student_id=${studentId}&password=${encodeURIComponent(password)}`
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Password updated successfully', 'success');
            closeModal();
        } else {
            showNotification(result.error || 'Failed to update password', 'error');
        }
    } catch (error) {
        showNotification('An error occurred', 'error');
    }
}

async function deleteStudent(studentId) {
    try {
        const response = await fetch('ajax-handlers.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete_student&student_id=${studentId}`
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Student deleted successfully', 'success');
            closeModal();
            
            // Remove row from table
            const row = document.querySelector(`tr[data-student-id="${studentId}"]`);
            if (row) {
                row.remove();
                filterTable(); // Update count
            }
        } else {
            showNotification(result.error || 'Failed to delete student', 'error');
        }
    } catch (error) {
        showNotification('An error occurred', 'error');
    }
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px; animation: slideInRight 0.3s ease;';
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Add animation styles
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// Toggle printed status
async function togglePrinted(studentId, printed, el) {
    try {
        const response = await fetch('ajax-handlers.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=mark_printed&student_id=${studentId}&printed=${printed}`
        });
        const result = await response.json();
        if (result.success) {
            if (printed === 1) {
                el.className = 'badge badge-success';
                el.style.cursor = 'pointer';
                el.title = 'Click to mark as not printed';
                el.onclick = function() { togglePrinted(studentId, 0, el); };
                el.innerHTML = '<svg style="width:12px;height:12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Printed';
            } else {
                el.className = 'badge badge-warning';
                el.style.cursor = 'pointer';
                el.title = 'Click to mark as printed';
                el.onclick = function() { togglePrinted(studentId, 1, el); };
                el.innerHTML = 'Not Printed';
            }
            showNotification(printed ? 'Marked as printed' : 'Marked as not printed', 'success');
        } else {
            showNotification(result.error || 'Failed to update', 'error');
        }
    } catch (e) {
        showNotification('An error occurred', 'error');
    }
}
