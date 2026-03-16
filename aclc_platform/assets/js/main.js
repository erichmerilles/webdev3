// Handle Liking a Post
function likePost(postId, btnElement) {
    fetch('ajax/process_like.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'post_id=' + postId
    })
        .then(response => response.text())
        .then(newLikeCount => {
            if (newLikeCount !== "error") {
                // Update button text and icon to filled state
                btnElement.innerHTML = `<i class="bi bi-hand-thumbs-up-fill"></i> Liked (${newLikeCount})`;
                // Change from the default secondary outline to the solid primary blue
                btnElement.classList.remove('btn-outline-secondary');
                btnElement.classList.add('btn-primary', 'text-white');
                btnElement.disabled = true; // Prevent multiple clicks
            }
        });
}

// Handle Submitting a Comment
function submitComment(postId) {
    const input = document.getElementById('commentInput_' + postId);
    const commentText = input.value.trim();

    if (commentText === "") return;

    // SECURE: We no longer send a hardcoded student_name. The backend handles identity!
    fetch('ajax/process_comment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `post_id=${postId}&comment_text=${encodeURIComponent(commentText)}`
    })
        .then(response => response.text())
        .then(data => {
            // Handle session expiration
            if (data === "error_auth") {
                alert("Your session expired. Please log in again.");
                window.location.reload();
            }
            // Handle successful comment
            else if (data !== "error") {
                // 1. Remove the "No comments yet" message if it exists
                const noCommentMsg = document.getElementById('noCommentMsg_' + postId);
                if (noCommentMsg) {
                    noCommentMsg.remove();
                }

                // 2. Create the new comment container
                const commentList = document.getElementById('commentList_' + postId);
                const newDiv = document.createElement('div');

                // Match the exact HTML structure and classes of the Bulletin Board layout
                newDiv.className = 'd-flex mb-3 pb-3 border-bottom border-light';
                newDiv.innerHTML = `
                    <i class='bi bi-person-circle fs-4 text-secondary me-3 mt-1'></i>
                    <div>
                        <div class='fw-bold text-dark small'>${data}</div>
                        <div class='text-secondary' style='font-size: 0.95rem;'>${commentText}</div>
                    </div>
                `;

                // 3. Append it to the list
                commentList.appendChild(newDiv);

                // 4. Clear the input field automatically
                input.value = "";
            } else {
                alert("An error occurred while posting your comment.");
            }
        });
}

document.addEventListener('DOMContentLoaded', function () {

    // 1. Toggle Event Date input based on Category Selection (Admin only)
    const postTypeSelect = document.getElementById('postTypeSelect');
    if (postTypeSelect) {
        postTypeSelect.addEventListener('change', function () {
            var eventDateContainer = document.getElementById('eventDateContainer');
            if (this.value === 'event') {
                eventDateContainer.style.display = 'block';
            } else {
                eventDateContainer.style.display = 'none';
            }
        });
    }

    // 2. Toggle Event Date inside the Edit Post Modal (Admin only)
    const editPostType = document.getElementById('editPostType');
    if (editPostType) {
        editPostType.addEventListener('change', function () {
            var editEventDateContainer = document.getElementById('editEventDateContainer');
            if (this.value === 'event') {
                editEventDateContainer.style.display = 'block';
            } else {
                editEventDateContainer.style.display = 'none';
            }
        });
    }

    // 3. Initialize Bootstrap Tooltips (Global)
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });

    // 4. Populate Edit Post Modal dynamically (Admin only)
    const editPostModal = document.getElementById('editPostModal');
    if (editPostModal) {
        editPostModal.addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget;
            document.getElementById('editPostId').value = button.getAttribute('data-id');
            document.getElementById('editPostTitle').value = button.getAttribute('data-title');
            document.getElementById('editPostContent').value = button.getAttribute('data-content');

            const type = button.getAttribute('data-type');
            document.getElementById('editPostType').value = type;

            const editEventDateContainer = document.getElementById('editEventDateContainer');
            if (type === 'event') {
                editEventDateContainer.style.display = 'block';
                document.getElementById('editEventDate').value = button.getAttribute('data-event-date');
            } else {
                editEventDateContainer.style.display = 'none';
                document.getElementById('editEventDate').value = '';
            }
        });
    }

    // 5. Populate Edit Bulletin Modal dynamically (Admin only)
    const editBulletinModal = document.getElementById('editBulletinModal');
    if (editBulletinModal) {
        editBulletinModal.addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget;
            document.getElementById('editXmlId').value = button.getAttribute('data-id');
            document.getElementById('editXmlCategory').value = button.getAttribute('data-category');
            document.getElementById('editXmlTitle').value = button.getAttribute('data-title');
            document.getElementById('editXmlDescription').value = button.getAttribute('data-description');
        });
    }

    // 6. Initialize DataTables for Pagination (Admin only)
    if (window.jQuery && $.fn.DataTable) {
        if ($('#publicationsTable').length) {
            $('#publicationsTable').DataTable({
                "pageLength": 5,
                "lengthChange": false,
                "ordering": false,
                "info": true,
                "language": {
                    "search": "",
                    "searchPlaceholder": "Search records..."
                }
            });
        }

        if ($('#bulletinsTable').length) {
            $('#bulletinsTable').DataTable({
                "pageLength": 3,
                "lengthChange": false,
                "ordering": false,
                "info": true,
                "language": {
                    "search": "",
                    "searchPlaceholder": "Search alerts..."
                }
            });
        }
    }
});

// 7. SweetAlert2 Logout Confirmation (Global)
function confirmLogout(event) {
    event.preventDefault();
    Swal.fire({
        title: 'Sign out?',
        text: "You are about to securely end your session.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#0f172a',
        cancelButtonColor: '#dc3545',
        confirmButtonText: 'Yes, log me out',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
        customClass: { popup: 'rounded-4' }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Logging out...',
                icon: 'success',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
                window.location.href = 'logout.php';
            });
        }
    })
}

// 8. SweetAlert2 Universal Delete Confirmation (Admin only)
function confirmDelete(deleteUrl, itemType) {
    Swal.fire({
        title: 'Are you sure?',
        text: "This " + itemType + " will be permanently deleted. This action cannot be undone.",
        icon: 'error',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, delete it!',
        reverseButtons: true,
        customClass: { popup: 'rounded-4' }
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = deleteUrl;
        }
    })
}

// 9. Smart Scroll Memory: Remember exact scroll position during page reloads (Global)
window.addEventListener('beforeunload', function () {
    sessionStorage.setItem('scrollPosition', window.scrollY);
});

window.addEventListener('load', function () {
    if (sessionStorage.getItem('scrollPosition') !== null) {
        window.scrollTo(0, parseInt(sessionStorage.getItem('scrollPosition')));
        sessionStorage.removeItem('scrollPosition');
    }
});

document.addEventListener('DOMContentLoaded', function () {
    const filterBtns = document.querySelectorAll('.filter-pill');
    const postCards = document.querySelectorAll('.post-card');
    const noResultsMsg = document.getElementById('noResultsMsg');

    filterBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            // 1. Remove active class from all buttons, add to clicked button
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            const filterValue = this.getAttribute('data-filter');
            let visibleCount = 0;

            // 2. Loop through posts and hide/show based on category
            postCards.forEach(card => {
                const category = card.getAttribute('data-category');

                if (filterValue === 'all' || filterValue === category) {
                    card.style.display = 'block';
                    // Optional: Add a quick fade-in animation
                    card.style.animation = 'fadeIn 0.4s ease forwards';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            // 3. Show "No Results" message if empty
            if (visibleCount === 0) {
                noResultsMsg.classList.remove('d-none');
            } else {
                noResultsMsg.classList.add('d-none');
            }
        });
    });
});