<div class="modal fade" id="addStudentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold">Add Student Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label text-secondary fw-semibold small">First Name</label><input type="text" name="first_name" class="form-control bg-light" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label text-secondary fw-semibold small">Last Name</label><input type="text" name="last_name" class="form-control bg-light" required></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label text-secondary fw-semibold small">USN (Username)</label><input type="text" name="username" class="form-control bg-light" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label text-secondary fw-semibold small">Email</label><input type="email" name="email" class="form-control bg-light" required></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-secondary fw-semibold small">Year Level</label>
                            <select name="year_level" class="form-select bg-light" required>
                                <option value="Grade 11">Grade 11</option>
                                <option value="Grade 12">Grade 12</option>
                                <option value="1st Year">1st Year</option>
                                <option value="2nd Year">2nd Year</option>
                                <option value="3rd Year">3rd Year</option>
                                <option value="4th Year">4th Year</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3"><label class="form-label text-secondary fw-semibold small">Section</label><input type="text" name="section" class="form-control bg-light" required></div>
                    </div>
                    <div class="mb-3"><label class="form-label text-secondary fw-semibold small">Password</label><input type="password" name="password" class="form-control bg-light" required></div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="submit_user" class="btn btn-info text-white rounded-pill px-4 fw-bold">Create Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editStudentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold">Edit Student Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="edit_user_id" id="editUserId">
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label text-secondary fw-semibold small">First Name</label><input type="text" name="edit_firstname" id="editFirstName" class="form-control bg-light" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label text-secondary fw-semibold small">Last Name</label><input type="text" name="edit_lastname" id="editLastName" class="form-control bg-light" required></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label text-secondary fw-semibold small">USN (Username)</label><input type="text" name="edit_username" id="editUsername" class="form-control bg-light" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label text-secondary fw-semibold small">Email</label><input type="email" name="edit_email" id="editEmail" class="form-control bg-light" required></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-secondary fw-semibold small">Year Level</label>
                            <select name="edit_year" id="editYear" class="form-select bg-light" required>
                                <option value="Grade 11">Grade 11</option>
                                <option value="Grade 12">Grade 12</option>
                                <option value="1st Year">1st Year</option>
                                <option value="2nd Year">2nd Year</option>
                                <option value="3rd Year">3rd Year</option>
                                <option value="4th Year">4th Year</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3"><label class="form-label text-secondary fw-semibold small">Section</label><input type="text" name="edit_section" id="editSection" class="form-control bg-light" required></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary fw-semibold small">New Password <span class="text-muted fw-normal">(Leave blank to keep current)</span></label>
                        <input type="password" name="edit_password" class="form-control bg-light">
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_user" class="btn btn-primary rounded-pill px-4 fw-bold">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editPostModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold">Edit Campus Post</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="edit_id" id="editPostId">
                    <div class="mb-3"><label class="form-label text-secondary fw-semibold small">Title</label><input type="text" name="title" id="editPostTitle" class="form-control bg-light" required></div>
                    <div class="mb-3">
                        <label class="form-label text-secondary fw-semibold small">Category</label>
                        <select name="type" id="editPostType" class="form-select bg-light">
                            <option value="news">News Article</option>
                            <option value="event">ACLC Event</option>
                        </select>
                    </div>
                    <div class="mb-3" id="editEventDateContainer" style="display: none;"><label class="form-label text-secondary fw-semibold small">Actual Event Date</label><input type="date" name="event_date" id="editEventDate" class="form-control bg-light"></div>
                    <div class="mb-3"><label class="form-label text-secondary fw-semibold small">Content</label><textarea name="content" id="editPostContent" class="form-control bg-light" rows="6" required></textarea></div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_post" class="btn btn-primary rounded-pill px-4 fw-bold">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editBulletinModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold">Edit Urgent Bulletin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="edit_xml_id" id="editXmlId">
                    <div class="mb-3"><label class="form-label text-secondary fw-semibold small">Category</label><input type="text" name="xml_category" id="editXmlCategory" class="form-control bg-light" required></div>
                    <div class="mb-3"><label class="form-label text-secondary fw-semibold small">Title</label><input type="text" name="xml_title" id="editXmlTitle" class="form-control bg-light" required></div>
                    <div class="mb-3"><label class="form-label text-secondary fw-semibold small">Description</label><input type="text" name="xml_description" id="editXmlDescription" class="form-control bg-light" required></div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_xml" class="btn btn-danger rounded-pill px-4 fw-bold">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>