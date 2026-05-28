jQuery(document).ready(function ($) {







  // ================================
  // COMPANY PROFILE FORM
  // ================================
  $(document).on('submit', '#company-profile-form', function(e){
    e.preventDefault();

    let formData = new FormData(this);
    formData.append('action', 'update_company_profile');

    $('#company-profile-msg').html('Saving...');

    $.ajax({
      url: mdAjax.ajaxurl,
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function(res){
        if(res.success){
          $('#company-profile-msg').html('<p style="color:green;">✅ Profile updated successfully</p>');
        } else {
          $('#company-profile-msg').html('<p style="color:red;">❌ '+res.data+'</p>');
        }
      },
      error: function(){
        $('#company-profile-msg').html('<p style="color:red;">❌ Server error</p>');
      }
    });
  });

  // ================================
  // ROUTING
  // ================================
$('.md-route').on('click', function (e) {
    e.preventDefault();

    let pageId = $(this).data('pageid');
    if (!pageId) return;

    // ✅ ACTIVE STATE
    $('.md-route').removeClass('active');
    $(this).addClass('active');

    localStorage.setItem('md_active_tab', pageId);

    $('.md-route').removeClass('active');
    $(this).addClass('active');


    // ✅ RESET EVERYTHING FIRST (CRITICAL FIX)
    $('#md-default-dashboard').hide();
    $('#md-my-courses').hide();
    $('#md-ce-credits').hide();
    $('#md-my-events').hide();
    $('#md-applications').hide();
    $('#md-dynamic-content').hide().empty();
    $('#md-loader').hide();

    // ============================
    // PRELOADED ROUTES
    // ============================
    if (pageId === 'APPLICATIONS') {
        return $('#md-applications').show();
    }

    if (pageId === 'dashboard') {
        return $('#md-default-dashboard').show();
    }

    if (pageId === 'my-courses') {
        return $('#md-my-courses').show();
    }

    if (pageId === 'ce-credits') {
        return $('#md-ce-credits').show();
    }

    if (pageId === 'my-events') {
        return $('#md-my-events').show();
    }

    // ============================
    // PROFILE (AJAX)
    // ============================
    if (pageId === 'PROFILE') {

        $('#md-loader').show();

        $.post(mdAjax.ajaxurl, {
            action: 'md_load_profile'
        }, function(res){
            $('#md-loader').hide();

            $('#md-dynamic-content')
                .html(res)
                .fadeIn(200);
        });

        return;
    }

    // ============================
    // OTHER AJAX PAGES
    // ============================
    if (isNaN(pageId)) return;

    $('#md-loader').show();

    $.ajax({
        url: mdAjax.ajaxurl,
        type: 'POST',
        data: {
            action: 'md_load_page',
            nonce: mdAjax.nonce,
            page_id: pageId
        },
        success: function (response) {
            $('#md-loader').hide();

            $('#md-dynamic-content')
                .html(response)
                .fadeIn(200);
        },
        error: function () {
            $('#md-loader').hide();
            $('#md-dynamic-content')
                .html('<p>Error loading content.</p>')
                .fadeIn(200);
        }
    });

});

});


// ========================================
// CSV + MODAL + MEMBER LOGIC
// ========================================
(function(){

let csvRows = [];
let currentIndex = 0;
let isProcessing = false;
let processedEmails = new Set();

function stopSpinner(message = '') {
    const statusBox = document.getElementById('import-status');

    if (message) updateStatus(message);

    if (statusBox) {
        statusBox.classList.add('completed');
        const spinner = statusBox.querySelector('.spinner');
        if (spinner) spinner.style.display = 'none';
    }
}

function updateStatus(text) {
    const el = document.getElementById('status-text');
    if (el) el.innerText = text;
}

function processNextRow() {

    if (isProcessing) return;

    if (currentIndex >= csvRows.length) {
        stopSpinner('✅ Import Complete');
        isProcessing = false;
        return;
    }

    isProcessing = true;

    const row = csvRows[currentIndex].split(',');
    const name  = (row[0] || '').trim();
    const email = (row[1] || '').trim().toLowerCase();

    if (!email || processedEmails.has(email)) {
        currentIndex++;
        isProcessing = false;
        return processNextRow();
    }

    processedEmails.add(email);
    updateStatus(`📄 Processing ${name}`);

    const data = new FormData();
    data.append('action', 'process_member_row');
    data.append('name', name);
    data.append('email', email);

    fetch('/wp-admin/admin-ajax.php', {
        method: 'POST',
        body: data
    })
    .then(res => res.json())
    .then(res => {
        if (!res.success) {
            updateStatus(`❌ Error: ${res.data}`);
            isProcessing = false;
            return;
        }

        handleSteps(res.data, () => {
            currentIndex++;
            isProcessing = false;
            processNextRow();
        });
    })
    .catch(() => {
        updateStatus('❌ Network error');
        isProcessing = false;
    });

}

function handleSteps(steps, callback) {
    let i = 0;

    function nextStep() {
        if (i >= steps.length) return callback();

        updateStatus(steps[i]);
        setTimeout(() => {
            i++;
            nextStep();
        }, 600);
    }

    nextStep();
}


// ================================
// CSV FORM
// ================================
document.addEventListener('submit', function(e){

    const form = e.target.closest('#csv-upload-form');
    if (!form) return;

    e.preventDefault();

    const fileInput = form.querySelector('input[type="file"]');
    if (!fileInput || !fileInput.files.length) return;
    const file = fileInput.files[0];
    const reader = new FileReader();

    reader.onload = function(event){

        const lines = event.target.result
            .replace(/\r/g, '')
            .split('\n')
            .filter(line => line.trim() !== '');

        if (lines.length > 0) {
            lines[0] = lines[0].replace(/^\uFEFF/, '');
        }

        csvRows = lines.slice(1);
        currentIndex = 0;
        isProcessing = false;
        processedEmails.clear();

        const statusBox = document.getElementById('import-status');
        if (statusBox) statusBox.style.display = 'block';

        updateStatus('🚀 Starting import...');
        processNextRow();
    };

    reader.readAsText(file);
});


// ================================
// MODAL + EDIT
// ================================
document.addEventListener('click', function(e){

    const editBtn = e.target.closest('.edit-member');
    if (editBtn) {

        const modal = document.getElementById('editModal');
        const fullName = editBtn.dataset.name || '';

const parts = fullName.trim().split(' ');

const firstName = parts.shift() || '';
const lastName  = parts.join(' ') || '';

document.getElementById('edit-first-name').value = firstName;
document.getElementById('edit-last-name').value  = lastName;


        document.getElementById('edit-userid').value = editBtn.dataset.userid || '';
    //    document.getElementById('edit-name').value = editBtn.dataset.name || '';
        document.getElementById('edit-email').value = editBtn.dataset.email || '';
        document.getElementById('edit-phone').value = editBtn.dataset.phone || '';
        document.getElementById('edit-address').value = editBtn.dataset.address || '';

        modal.style.display = 'flex';
    }

    if (e.target.closest('.close-modal')) {
        document.getElementById('editModal').style.display = 'none';
    }

    const saveBtn = e.target.closest('#save-member');
    if (saveBtn) {

        const data = new FormData();

        data.append('action', 'update_member');
        data.append('user_id', document.getElementById('edit-userid').value);
        const firstName = document.getElementById('edit-first-name').value.trim();
const lastName  = document.getElementById('edit-last-name').value.trim();

const fullName = (firstName + ' ' + lastName).trim();

data.append('name', fullName);
        data.append('phone', document.getElementById('edit-phone').value);
        data.append('address', document.getElementById('edit-address').value);

        const btn = document.getElementById('save-member');

        btn.classList.add('loading');
        
    fetch('/wp-admin/admin-ajax.php', {
    method: 'POST',
    body: data
})
.then(res => res.json())
.then(res => {

    if (res.success) {

        showSuccessModal('Updated successfully');

        const userId = document.getElementById('edit-userid').value;

        // 🔥 FIND ROW
        const row = document.querySelector(`[data-userid="${userId}"]`);

        if (row) {
            
            // 🔥 GET UPDATED VALUES
            const firstName = document.getElementById('edit-first-name').value.trim();
            const lastName  = document.getElementById('edit-last-name').value.trim();
            const email     = document.getElementById('edit-email').value.trim();
            const fullName = (firstName + ' ' + lastName).trim();
            console.log(firstName);
            // 🔥 UPDATE UI (IMPORTANT: match your classes)
            const nameEl  = row.querySelector('.member-name');
            const emailEl = row.querySelector('.member-email');
        

            if (nameEl)  nameEl.textContent  = fullName;
            if (emailEl) emailEl.textContent = email;
     

            // 🔥 UPDATE EDIT BUTTON DATA (VERY IMPORTANT)
            const editBtn = row.querySelector('.edit-member');

            if (editBtn) {
                editBtn.dataset.name  = fullName;
                editBtn.dataset.email = email;
            
            }

            // 🔥 OPTIONAL: highlight row
            row.style.background = '#f3f4f6';
            setTimeout(() => {
                row.style.background = '';
            }, 1200);
        }

        // close modal
        document.getElementById('editModal').style.display = 'none';
    }

})
.finally(() => {
    btn.classList.remove('loading');
});
    }

});

})(); // ✅ ONLY ONE VALID CLOSING HERE


// ========================================
// DELETE MODULE
// ========================================
(function(){

let deleteUserId = null;

document.addEventListener('click', function(e){

    const btn = e.target.closest('.delete-member');
    if (!btn) return;

    deleteUserId = btn.dataset.userid;

    const modal = document.getElementById('deleteModal');
    if (modal) modal.classList.add('active');
});

document.addEventListener('click', function(e){

    if (e.target.id === 'cancel-delete') {
        document.getElementById('deleteModal').classList.remove('active');
        deleteUserId = null;
    }

});

document.addEventListener('click', function(e){

    if (e.target.id !== 'confirm-delete') return;
    if (!deleteUserId) return;

    const btn = e.target;
    btn.innerText = 'Deleting...';
    btn.disabled = true;

    const data = new FormData();
    data.append('action', 'delete_member');
    data.append('user_id', deleteUserId);

    fetch('/wp-admin/admin-ajax.php', {
        method: 'POST',
        body: data
    })
    .then(res => res.json())
    .then(res => {
       
        if (res.success) {
            
            const row = document.querySelector(`[data-userid="${deleteUserId}"]`)?.closest('tr');
            if (row) row.remove();

            document.getElementById('deleteModal').classList.remove('active');
            deleteUserId = null;

        } else {
            alert('Error: ' + res.data);
        }

    })
    .catch(() => alert('Server error'))
    .finally(() => {
        btn.innerText = 'Yes, Delete';
        btn.disabled = false;
    });

});

})();



document.addEventListener('click', function(e){

    if (e.target.id === 'success-ok') {

        document.getElementById('successModal').classList.remove('active');


    }

});


document.addEventListener('submit', function(e){

    if (!e.target.matches('#my-profile-form')) return;

    e.preventDefault();

    const form = e.target;
    const data = new FormData(form);

    data.append('action', 'save_my_profile');

    // image
    const fileInput = document.getElementById('profile-image');

    if (fileInput.files[0]) {
        const reader = new FileReader();

        reader.onload = function(event){

            data.append('user_image', event.target.result);

            sendProfile(data);
        };

        reader.readAsDataURL(fileInput.files[0]);
    } else {
        sendProfile(data);
    }

    function sendProfile(data){
        fetch('/wp-admin/admin-ajax.php', {
            method: 'POST',
            body: data
        })
        .then(res => res.json())
        .then(res => {
            if(res.success){
                showSuccessModal('Updated successfully');
            }
        });
    }

});

document.addEventListener('change', function(e){

    if (e.target.id !== 'profile-image') return;

    const file = e.target.files[0];
    if (!file) return;

    const reader = new FileReader();

    reader.onload = function(e){
        document.getElementById('profile-preview').src = e.target.result;
    };

    reader.readAsDataURL(file);
});












// ==============================
// ACCORDION TOGGLE
// ==============================
document.addEventListener('click', function(e){

    const header = e.target.closest('.acc-header');
    if (!header) return;

    const item = header.parentElement;

    // close others (optional - remove if you want multiple open)
    document.querySelectorAll('.acc-item').forEach(el => {
        if (el !== item) el.classList.remove('active');
    });

    item.classList.toggle('active');
});


// ==============================
// PROFILE IMAGE (BASE64 PREVIEW)
// ==============================
let profileImageBase64 = '';

document.addEventListener('change', function(e){

    if (e.target.id !== 'profile-image') return;

    const file = e.target.files[0];
    if (!file) return;

    const reader = new FileReader();

    reader.onload = function(ev){
        profileImageBase64 = ev.target.result;
        document.getElementById('profile-preview').src = profileImageBase64;
    };

    reader.readAsDataURL(file);
});


// ==============================
// PASSWORD STRENGTH
// ==============================
document.addEventListener('input', function(e){

    if (e.target.id !== 'new-password') return;

    const val = e.target.value;
    const bar = document.querySelector('.strength-bar');
    const text = document.querySelector('.strength-text');

    let score = 0;

    if (val.length >= 6) score++;
    if (val.match(/[A-Z]/)) score++;
    if (val.match(/[0-9]/)) score++;
    if (val.match(/[^A-Za-z0-9]/)) score++;

    let width = ['0%','25%','50%','75%','100%'][score];
    let label = ['','Weak','Okay','Good','Strong'][score];

    bar.style.width = width;
    text.innerText = label;

    bar.className = 'strength-bar level-' + score;
});


// ==============================
// SAVE PROFILE (AJAX)
// ==============================
document.addEventListener('submit', function(e){

    if (e.target.id !== 'my-profile-form') return;

    e.preventDefault();

    const form = e.target;
    const btn = form.querySelector('button');

    const data = new FormData(form);
    data.append('action', 'save_my_profile');

    if (profileImageBase64) {
        data.append('user_image', profileImageBase64);
    }

    btn.classList.add('loading');

    fetch('/wp-admin/admin-ajax.php', {
        method: 'POST',
        body: data
    })
    .then(res => res.json())
    .then(res => {

        if (res.success) {

            btn.innerText = 'Saved ✓';

            setTimeout(() => {
                btn.innerText = 'Save Profile';
            }, 2000);

        } else {
            alert(res.data || 'Error');
        }

    })
    .catch(() => alert('Server error'))
    .finally(() => {
        btn.classList.remove('loading');
    });

});





// ===============================
// LOGOUT MODAL
// ===============================

// OPEN MODAL
document.addEventListener('click', function(e){

    const btn = e.target.closest('#open-logout-modal');

    if (!btn) return;

    e.preventDefault();

    console.log("Log Out Action");

    document.getElementById('logoutModal').classList.add('active');

});

// CANCEL
document.addEventListener('click', function(e){

    if (e.target.id === 'cancel-logout') {
        document.getElementById('logoutModal').classList.remove('active');
    }

});

// CONFIRM LOGOUT
document.addEventListener('click', function(e){

    if (e.target.id !== 'confirm-logout') return;

    const btn = e.target;
    btn.innerText = 'Logging out...';
    btn.disabled = true;

    const data = new FormData();
    data.append('action', 'ajax_logout');

    fetch('/wp-admin/admin-ajax.php', {
        method: 'POST',
        body: data
    })
    .then(res => res.json())
    .then(res => {

        if (res.success) {
            window.location.href = '/logout';
        } else {
            alert('Logout failed');
        }

    })
    .catch(() => alert('Server error'))
    .finally(() => {
        btn.innerText = 'Yes';
        btn.disabled = false;
    });

});


function showSuccessModal(message) {
    const modal = document.getElementById('successModal');
    const msg   = document.getElementById('successMessage');

    if (msg) msg.innerText = message;
    if (modal) modal.classList.add('active');
}


document.addEventListener('input', function (e) {

    if (!e.target.classList.contains('corp-phone')) return;

    let input = e.target;
    let value = input.value.replace(/\D/g, ''); // remove non-digits

    // Limit to 10 digits (US format)
    value = value.substring(0, 10);

    let formatted = '';

    if (value.length > 0) {
        formatted = '(' + value.substring(0, 3);
    }
    if (value.length >= 4) {
        formatted += ') ' + value.substring(3, 6);
    }
    if (value.length >= 7) {
        formatted += '-' + value.substring(6, 10);
    }

    input.value = formatted;

});


document.addEventListener('click', function(e){

    if (e.target.id === 'btn-individual') {
        const form = document.getElementById('event-individual-form');

        if (form) {
            form.style.display = 'block';

            const offset = 300; // 👈 adjust this (px space from top)
            const top = form.getBoundingClientRect().top + window.pageYOffset - offset;

            window.scrollTo({
                top: top,
                behavior: 'smooth'
            });
        }
    }

});




// ================================
// ADD MEMBER MODAL
// ================================

// OPEN MODAL
document.addEventListener('click', function(e){
    if (e.target.classList.contains('add_single_member')) {
        document.getElementById('addMemberModal').style.display = 'flex';
    }
});

// CLOSE MODAL
document.addEventListener('click', function(e){
    if (e.target.classList.contains('close-modal')) {
        document.getElementById('addMemberModal').style.display = 'none';
    }
});

// EXISTING USER POPUP CLOSE
document.addEventListener('click', function(e){
    if (e.target.id === 'existing-ok') {
        document.getElementById('existingUserModal').style.display = 'none';
    }
});





















// ================================
// SAVE MEMBER (FULL FIXED VERSION)
// ================================
document.addEventListener('click', function(e){

    const btn = e.target.closest('#create-member-btn');
    if (!btn) return;

    btn.classList.add('loading');

    const data = new FormData();

    data.append('action', 'add_new_member');
    data.append('first_name', document.getElementById('new-first-name').value);
    data.append('last_name', document.getElementById('new-last-name').value);
    data.append('email', document.getElementById('new-email').value);
    data.append('phone', document.getElementById('new-phone').value);

    fetch('/wp-admin/admin-ajax.php', {
        method: 'POST',
        body: data
    })
    .then(res => res.json())
    .then(res => {

        console.log('AJAX RESPONSE:', res);

        // ========================
        // ❌ USER EXISTS IN OTHER ORG
        // ========================
        if (res.data && res.data.exists_other_org) {
            document.getElementById('existingUserModal').style.display = 'flex';
            return;
        }

        // ========================
        // ✅ SUCCESS - MEMBER CREATED
        // ========================
        if (res.success && res.data.created) {

            const user = res.data;

            const fullName = (user.first_name + ' ' + user.last_name).trim();

            // 🔥 BUILD ROW (MISSING PART FIXED)
            const newRow = `
                <tr data-userid="${user.user_id}">
                    <td class="member-name">${fullName}</td>
                    <td class="member-email">${user.email}</td>
                    <td>
                        <span class="member_role member">Member</span>
                    </td>
                    <td>
                        <a href="#"
                           class="edit-member action-btn edit-btn"
                           data-userid="${user.user_id}"
                           data-name="${fullName}"
                           data-email="${user.email}"
                           data-phone="${user.phone}"
                           data-address="">
                           Edit
                        </a>
                        &nbsp;
                        <a href="#"
                           class="delete-member action-btn delete-btn"
                           data-userid="${user.user_id}">
                           Delete
                        </a>
                    </td>
                </tr>
            `;

            // 🔥 FIND TABLE SAFELY
            const tableBody = document.querySelector('#member-panel-container .member-table tbody');

            if (!tableBody) {
                console.error('❌ Table body not found');
                return;
            }

            // 🔥 APPEND ROW
            tableBody.insertAdjacentHTML('beforeend', newRow);

            // 🔥 HIGHLIGHT NEW ROW
            const lastRow = tableBody.lastElementChild;
            if (lastRow) {
                lastRow.style.background = '#ecfdf5';
                lastRow.scrollIntoView({ behavior: 'smooth', block: 'center' });

                setTimeout(() => {
                    lastRow.style.background = '';
                }, 1500);
            }

            // ========================
            // 🔥 FIX MODAL STACKING
            // ========================
            document.getElementById('addMemberModal').style.display = 'none';

            showSuccessModal('Member added successfully');

            // ========================
            // 🔄 RESET FORM
            // ========================
            document.getElementById('new-first-name').value = '';
            document.getElementById('new-last-name').value = '';
            document.getElementById('new-email').value = '';
            document.getElementById('new-phone').value = '';
        }

    })
    .catch(err => {
        console.error('❌ AJAX ERROR:', err);
    })
    .finally(() => {
        btn.classList.remove('loading');
    });

});