// ==========================================
// 1. POST INTERACTIONS (Likes & Comments)
// ==========================================

// Handle Liking/Unliking a Post (Toggle)
function likePost(postId, btnElement) {
    const isLiked = btnElement.classList.contains('bg-primary');
    const action = isLiked ? 'unlike' : 'like';
    const countSpan = document.getElementById('likeCount_' + postId);
    let count = parseInt(countSpan.innerText);

    // Instant UI Update for snappy feel
    if (action === 'like') {
        count++;
        btnElement.classList.add('bg-primary', 'text-white');
        btnElement.classList.remove('btn-elegant');
        btnElement.innerHTML = `<i class="bi bi-heart-fill me-2"></i> <span id="likeCount_${postId}">${count}</span>`;
    } else {
        count--;
        btnElement.classList.remove('bg-primary', 'text-white');
        btnElement.classList.add('btn-elegant');
        btnElement.innerHTML = `<i class="bi bi-heart me-2"></i> <span id="likeCount_${postId}">${count}</span>`;
    }

    // Send action to backend
    fetch('ajax/process_like.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `post_id=${postId}&action=${action}`
    });
}

// Handle Submitting a Main Top-Level Comment
function submitComment(postId) {
    const input = document.getElementById('commentInput_' + postId);
    const commentText = input.value.trim();

    if (commentText === "") return;

    fetch('ajax/process_comment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `post_id=${postId}&comment_text=${encodeURIComponent(commentText)}`
    })
        .then(response => response.text())
        .then(data => {
            if (data === "error_auth") {
                alert("Your session expired. Please log in again.");
                window.location.reload();
            } else if (data !== "error") {
                const noCommentMsg = document.getElementById('noCommentMsg_' + postId);
                if (noCommentMsg) noCommentMsg.remove();

                const commentList = document.getElementById('commentList_' + postId);
                const newDiv = document.createElement('div');
                newDiv.className = 'mb-3';

                // Generate a temporary ID so the like/reply buttons work instantly
                const tempId = Math.floor(Math.random() * 100000);

                newDiv.innerHTML = `
                    <span class='fw-bold small text-dark'>${data}</span>
                    <p class='small text-muted mb-1'>${commentText}</p>
                    <div class='d-flex gap-3 align-items-center mb-2'>
                        <button class='btn btn-link p-0 text-muted small text-decoration-none' style='font-size: 0.8rem;' onclick='likeComment(${tempId}, this)'><i class='bi bi-hand-thumbs-up'></i> <span id='commentLike_${tempId}'>0</span></button>
                        <button class='btn btn-link p-0 text-muted small text-decoration-none' style='font-size: 0.8rem;' onclick='toggleReplyBox(${tempId})'>Reply</button>
                    </div>
                    <div id='replyBox_${tempId}' class='d-none mb-3'>
                        <div class='input-group rounded-pill overflow-hidden border shadow-sm input-group-sm'>
                            <input type='text' id='replyInput_${tempId}' class='form-control border-0 ps-3' placeholder='Write a reply...'>
                            <button class='btn btn-primary px-3' onclick='submitReply(${postId}, ${tempId})'>Post</button>
                        </div>
                    </div>
                    <div class='ms-4 ps-3 border-start border-2 border-primary border-opacity-25' id='replyList_${tempId}'></div>
                `;

                commentList.appendChild(newDiv);
                input.value = "";
            } else {
                alert("An error occurred while posting your comment.");
            }
        });
}

// Toggle the hidden reply input box
function toggleReplyBox(commentId) {
    const box = document.getElementById('replyBox_' + commentId);
    box.classList.toggle('d-none');
}

// Handle Liking/Unliking a specific Comment or Reply (Toggle)
function likeComment(commentId, btnElement) {
    const isLiked = btnElement.classList.contains('text-primary');
    const action = isLiked ? 'unlike' : 'like';
    const countSpan = document.getElementById('commentLike_' + commentId);
    let count = parseInt(countSpan.innerText);

    // Instant UI Update
    if (action === 'like') {
        count++;
        btnElement.classList.add('text-primary');
        btnElement.classList.remove('text-muted');
        btnElement.innerHTML = `<i class="bi bi-hand-thumbs-up-fill"></i> <span id="commentLike_${commentId}">${count}</span>`;
    } else {
        count--;
        btnElement.classList.remove('text-primary');
        btnElement.classList.add('text-muted');
        btnElement.innerHTML = `<i class="bi bi-hand-thumbs-up"></i> <span id="commentLike_${commentId}">${count}</span>`;
    }

    // Send action to backend
    fetch('ajax/process_comment_like.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `comment_id=${commentId}&action=${action}`
    });
}

// Handle Submitting a Reply to a Comment (Multiple Replies Allowed)
function submitReply(postId, parentId) {
    const input = document.getElementById('replyInput_' + parentId);
    const replyText = input.value.trim();

    if (replyText === "") return;

    fetch('ajax/process_comment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `post_id=${postId}&parent_id=${parentId}&comment_text=${encodeURIComponent(replyText)}`
    })
        .then(response => response.text())
        .then(data => {
            if (data === "error_auth") {
                alert("Your session expired. Please log in again.");
                window.location.reload();
            } else if (data !== "error") {
                // Notice we DO NOT hide the reply box here anymore, allowing infinite replies

                const replyList = document.getElementById('replyList_' + parentId);
                const newDiv = document.createElement('div');
                newDiv.className = 'mb-2';

                const tempId = Math.floor(Math.random() * 100000);

                newDiv.innerHTML = `
                    <span class='fw-bold small text-dark'>${data}</span>
                    <p class='small text-muted mb-1'>${replyText}</p>
                    <button class='btn btn-link p-0 text-muted small text-decoration-none' style='font-size: 0.75rem;' onclick='likeComment(${tempId}, this)'>
                        <i class='bi bi-hand-thumbs-up'></i> <span id='commentLike_${tempId}'>0</span>
                    </button>
                `;

                replyList.appendChild(newDiv);
                input.value = ""; // Clear input for the next reply
            } else {
                alert("An error occurred while posting your reply.");
            }
        });
}


// ==========================================
// 2. DOM LOADED & GLOBAL LISTENERS
// ==========================================
document.addEventListener('DOMContentLoaded', function () {

    // --- Feed Filter Pill Logic ---
    const filterBtns = document.querySelectorAll('.filter-pill');
    const postCards = document.querySelectorAll('.post-card');
    const noResultsMsg = document.getElementById('noResultsMsg');

    if (filterBtns.length > 0) {
        filterBtns.forEach(btn => {
            btn.addEventListener('click', function () {
                filterBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                const filterValue = this.getAttribute('data-filter');
                let visibleCount = 0;

                postCards.forEach(card => {
                    const category = card.getAttribute('data-category');

                    if (filterValue === 'all' || filterValue === category) {
                        card.style.display = 'block';
                        card.style.animation = 'fadeIn 0.4s ease forwards';
                        visibleCount++;
                    } else {
                        card.style.display = 'none';
                    }
                });

                if (visibleCount === 0) {
                    noResultsMsg.classList.remove('d-none');
                } else {
                    noResultsMsg.classList.add('d-none');
                }
            });
        });
    }

    // --- Admin Event Date Toggles ---
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

    // --- Bootstrap Tooltips ---
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });

    // --- Admin Edit Modals ---
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

    // --- Admin DataTables ---
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


// ==========================================
// 3. SWEETALERT UTILITIES
// ==========================================

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


// ==========================================
// 4. SMART SCROLL MEMORY
// ==========================================
window.addEventListener('beforeunload', function () {
    sessionStorage.setItem('scrollPosition', window.scrollY);
});

window.addEventListener('load', function () {
    if (sessionStorage.getItem('scrollPosition') !== null) {
        window.scrollTo(0, parseInt(sessionStorage.getItem('scrollPosition')));
        sessionStorage.removeItem('scrollPosition');
    }
});