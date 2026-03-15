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