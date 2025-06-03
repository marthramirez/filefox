<template>
  <div class="dashboard">
    <!-- Topbar -->
    <header
      class="topbar d-flex justify-content-between align-items-center px-3 shadow-sm"
    >
      <button class="btn btn-outline-primary d-md-none" @click="toggleSidebar">
        <i class="bi bi-list fs-4"></i>
      </button>

      <div class="search-wrapper mx-3">
        <div class="input-group">
          <span class="input-group-text bg-white border-end-0">
            <i class="bi bi-search"></i>
          </span>
          <input
            type="search"
            v-model="searchQuery"
            @keyup.enter="performSearch"
            class="form-control border-start-0"
            placeholder="Search documents, folders..."
          />
        </div>
      </div>

      <div class="position-relative">
        <div
          class="account-icon bg-primary text-white rounded-circle d-flex justify-content-center align-items-center"
          ref="accountIcon"
          @click="toggleAccountMenu"
        >
          <span>{{ userInitial }}</span>
        </div>

        <div
          v-if="showAccountMenu"
          class="account-menu shadow-sm"
          ref="accountMenu"
        >
          <div class="text-center">
            <p class="mb-0 fw-semibold">{{ currentUserEmail }}</p>
            <div class="account-circle my-2">
              <span>{{ userInitial }}</span>
            </div>
            <p class="fw-semibold">Hi, {{ userFname }}!</p>
            <button
              class="btn btn-outline-primary w-100 mb-2"
              @click="openUpdateAccountModal"
            >
              Manage Account
            </button>
            <button
              class="btn btn-light w-100 text-danger"
              @click="openLogoutModal"
            >
              <i class="bi bi-box-arrow-right me-2"></i>Sign out
            </button>
          </div>
        </div>
      </div>
    </header>

    <!-- Sidebar -->
    <transition name="slide">
      <aside v-if="sidebarVisible" class="sidebar bg-white shadow-sm">
        <div class="sidebar-header ps-3 pt-4 mb-4">
          <span class="fs-5 fw-bold text-primary">🦊 FileFox</span>
        </div>

        <ul class="nav flex-column px-2">
          <li class="nav-item" @click="navigateTo('/dashboard')">
            <a class="nav-link"
              ><i class="bi bi-house-door me-2"></i> Dashboard</a
            >
          </li>

          <li class="text-muted small fw-bold mt-3 mb-1 ps-2">Documents</li>
          <li class="nav-item" @click="navigateTo('/folders')">
            <a class="nav-link"><i class="bi bi-folder me-2"></i> My Folders</a>
          </li>
          <li class="nav-item" @click="navigateTo('/uploads')">
            <a class="nav-link"
              ><i class="bi bi-cloud-upload me-2"></i> Uploaded Files</a
            >
          </li>
          <li class="nav-item" @click="navigateTo('/shared')">
            <a class="nav-link"
              ><i class="bi bi-folder-symlink me-2"></i> Shared with Me</a
            >
          </li>
          <li class="nav-item" @click="navigateTo('/trash')">
            <a class="nav-link"><i class="bi bi-trash me-2"></i> Trash</a>
          </li>

          <li class="text-muted small fw-bold mt-4 mb-1 ps-2">Teams</li>
          <li class="nav-item" @click="navigateTo('/teams')">
            <a class="nav-link"><i class="bi bi-people me-2"></i> My Teams</a>
          </li>
          <li class="nav-item" @click="navigateTo('/invitations')">
            <a class="nav-link"
              ><i class="bi bi-envelope-open me-2"></i> Invitations</a
            >
          </li>
        </ul>
      </aside>
    </transition>

    <!-- Main content -->
    <main :class="['main-content', { 'with-sidebar': isDesktop }]">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold text-primary mb-0">
          {{ $route.params.folder_name }}
        </h4>
      </div>

      <!-- Update Account Modal -->
      <div
        v-if="showUpdateAccountModal"
        class="modal fade show centered-modal"
        tabindex="-1"
        role="dialog"
        aria-modal="true"
        style="display: block; background: rgba(0, 0, 0, 0.4)"
        @click.self="closeUpdateAccountModal"
      >
        <div class="modal-dialog" role="document">
          <div class="modal-content shadow" @click.stop>
            <form @submit.prevent="updateAccount">
              <div
                class="modal-header d-flex justify-content-between align-items-center"
              >
                <h5 class="modal-title">Update Account</h5>
                <button
                  type="button"
                  class="btn"
                  aria-label="Close"
                  @click="closeUpdateAccountModal"
                  style="
                    border: none;
                    background: transparent;
                    font-size: 1.25rem;
                  "
                >
                  <i class="bi bi-x-lg"></i>
                </button>
              </div>

              <div class="modal-body">
                <div class="mb-3">
                  <label for="fname" class="form-label">First Name</label>
                  <input
                    id="fname"
                    v-model="form.fname"
                    type="text"
                    class="form-control"
                    required
                    maxlength="50"
                  />
                </div>

                <div class="mb-3">
                  <label for="lname" class="form-label">Last Name</label>
                  <input
                    id="lname"
                    v-model="form.lname"
                    type="text"
                    class="form-control"
                    required
                    maxlength="50"
                  />
                </div>

                <div class="mb-3">
                  <label for="password" class="form-label"
                    >New Password (optional)</label
                  >
                  <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="form-control"
                    minlength="8"
                    placeholder="Leave blank to keep current password"
                  />
                </div>

                <div class="mb-3">
                  <label for="password_confirmation" class="form-label"
                    >Confirm Password</label
                  >
                  <input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    class="form-control"
                    :required="form.password.length > 0"
                    minlength="8"
                    placeholder="Confirm new password"
                  />
                </div>

                <div v-if="errorMessage" class="text-danger mb-2">
                  {{ errorMessage }}
                </div>
                <div v-if="successMessage" class="text-success mb-2">
                  {{ successMessage }}
                </div>
              </div>

              <div class="modal-footer">
                <button
                  type="button"
                  class="btn btn-secondary"
                  @click="closeUpdateAccountModal"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  class="btn btn-primary"
                  :disabled="loading"
                >
                  {{ loading ? "Updating..." : "Update" }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Logout Confirmation Modal -->
      <div
        v-if="showLogoutModal"
        class="modal d-block"
        tabindex="-1"
        style="background: rgba(0, 0, 0, 0.5)"
      >
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Confirm Logout</h5>
              <button
                type="button"
                class="btn-close"
                @click="closeLogoutModal"
              ></button>
            </div>
            <div class="modal-body">Are you sure you want to log out?</div>
            <div class="modal-footer justify-content-end">
              <button
                type="button"
                class="btn btn-link text-danger"
                @click="closeLogoutModal"
              >
                Cancel
              </button>
              <button
                type="button"
                class="btn btn-link text-primary"
                @click="logout"
              >
                Logout
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Error Modal -->
      <div id="errorModal" class="modal-overlay hidden">
        <div class="modal-box">
          <div class="modal-message-container">
            <p id="errorMessage">An error occurred.</p>
          </div>
          <button id="closeModalBtn">OK</button>
        </div>
      </div>

      <!-- Update Document Modal -->
      <div
        v-if="openFileUpdateModal"
        class="modal fade show centered-modal"
        style="display: block; background: rgba(0, 0, 0, 0.4)"
        @click.self="closeFileDetailsModal"
      >
        <div class="modal-dialog" role="document">
          <div class="modal-content shadow" @click.stop>
            <form @submit.prevent="updateDocument">
              <!-- MODAL HEADER WITH X ON THE RIGHT -->
              <div
                class="modal-header d-flex justify-content-between align-items-center"
              >
                <h5 class="modal-title mb-0">Document Details</h5>
                <button
                  type="button"
                  class="btn"
                  aria-label="Close"
                  @click="closeFileDetailsModal"
                  style="
                    border: none;
                    background: transparent;
                    font-size: 1.25rem;
                  "
                >
                  <i class="bi bi-x-lg"></i>
                </button>
              </div>

              <div class="modal-body">
                <div class="mb-3">
                  <label for="editTitle" class="form-label">Title</label>
                  <input
                    type="text"
                    id="editTitle"
                    class="form-control"
                    v-model="file_form.title"
                    required
                  />
                </div>

                <div class="mb-3">
                  <label for="editDescription" class="form-label"
                    >Description</label
                  >
                  <textarea
                    id="editDescription"
                    class="form-control"
                    v-model="file_form.description"
                    rows="3"
                  ></textarea>
                </div>

                <div class="mb-3">
                  <label for="editFileInput" class="form-label"
                    >Replace File (Optional)</label
                  >
                  <input
                    type="file"
                    id="editFileInput"
                    class="form-control"
                    accept=".pdf,.docx"
                    @change="handleFileChange"
                  />
                  <div class="text-danger mt-1" v-if="fileError">
                    {{ fileError }}
                  </div>
                  <div
                    class="text-muted mt-1"
                    v-if="file_form.file_name && !selectedFile"
                  >
                    Current File: {{ file_form.file_name }}
                  </div>
                </div>
              </div>

              <div class="modal-footer">
                <button
                  type="button"
                  class="btn btn-secondary"
                  @click="closeFileDetailsModal"
                >
                  Cancel
                </button>
                <button type="submit" class="btn btn-primary">
                  Save Changes
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Viewing Document Modal -->
      <div
        v-if="showViewDetailsModal"
        class="modal fade show centered-modal"
        style="display: block; background: rgba(0, 0, 0, 0.4)"
        @click.self="closeFileDetailsModal"
      >
        <div class="modal-dialog" role="document">
          <div class="modal-content shadow" @click.stop>
            <!-- MODAL HEADER -->
            <div
              class="modal-header d-flex justify-content-between align-items-center"
            >
              <h5 class="modal-title mb-0">Document Details</h5>
              <button
                type="button"
                class="btn"
                aria-label="Close"
                @click="closeFileDetailsModal"
                style="
                  border: none;
                  background: transparent;
                  font-size: 1.25rem;
                "
              >
                <i class="bi bi-x-lg"></i>
              </button>
            </div>

            <!-- MODAL BODY -->
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label fw-bold">Title</label>
                <p class="form-control-plaintext">{{ file_form.title }}</p>
              </div>

              <div class="mb-3">
                <label class="form-label fw-bold">Description</label>
                <p class="form-control-plaintext">
                  {{ file_form.description || "—" }}
                </p>
              </div>

              <div class="mb-3">
                <label class="form-label fw-bold">File Name</label>
                <p class="form-control-plaintext">
                  <a
                    v-if="file_form.file_name"
                    href="#"
                    class="text-primary text-decoration-underline"
                    @click.prevent="handleFileClick(file_form)"
                  >
                    {{ file_form.file_name }}
                  </a>
                  <span v-else class="text-muted">No file uploaded</span>
                </p>
              </div>
            </div>

            <!-- MODAL FOOTER -->
            <div class="modal-footer">
              <button
                type="button"
                class="btn btn-secondary"
                @click="closeFileDetailsModal"
              >
                Close
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Add Folder Modal -->
      <div
        id="add-folder-modal"
        v-if="showNewFolderModal"
        class="modal fade show centered-modal"
        tabindex="-1"
        style="display: block; background: rgba(0, 0, 0, 0.4)"
        ref="newFolderModal"
        role="dialog"
        aria-modal="true"
      >
        <div class="modal-dialog">
          <div class="modal-content shadow">
            <form @submit.prevent="submitNewFolder">
              <div
                class="modal-header d-flex justify-content-between align-items-center"
              >
                <h5 class="modal-title">Create New Folder</h5>
                <button
                  type="button"
                  class="btn"
                  aria-label="Close"
                  @click="closeNewFolderModal"
                  style="
                    border: none;
                    background: transparent;
                    font-size: 1.25rem;
                  "
                >
                  <i class="bi bi-x-lg"></i>
                </button>
              </div>

              <div class="modal-body">
                <div class="mb-3">
                  <label for="folderName" class="form-label">Folder Name</label>
                  <input
                    type="text"
                    id="folderName"
                    class="form-control"
                    v-model="newFolderName"
                    required
                    autofocus
                  />
                </div>
              </div>
              <div class="modal-footer">
                <button
                  type="button"
                  class="btn btn-secondary"
                  @click="closeNewFolderModal"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  class="btn btn-primary"
                  @click="createNewFolder"
                >
                  Create
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Rename Folder Modal -->
      <div
        id="rename-folder-modal"
        v-if="showRenameFolderModal"
        class="modal fade show centered-modal"
        tabindex="-1"
        style="display: block; background: rgba(0, 0, 0, 0.4)"
        ref="renameFolderModal"
        role="dialog"
        aria-modal="true"
      >
        <div class="modal-dialog">
          <div class="modal-content shadow">
            <form @submit.prevent="submitRenameFolder">
              <div
                class="modal-header d-flex justify-content-between align-items-center"
              >
                <h5 class="modal-title">Rename Folder</h5>
                <button
                  type="button"
                  class="btn"
                  aria-label="Close"
                  @click="closeRenameFolderModal"
                  style="
                    border: none;
                    background: transparent;
                    font-size: 1.25rem;
                  "
                >
                  <i class="bi bi-x-lg"></i>
                </button>
              </div>

              <div class="modal-body">
                <div class="mb-3">
                  <label for="renameFolderName" class="form-label"
                    >Folder Name</label
                  >
                  <input
                    type="text"
                    id="renameFolderName"
                    class="form-control"
                    v-model="renameFolderName"
                    required
                    autofocus
                  />
                </div>
              </div>
              <div class="modal-footer">
                <button
                  type="button"
                  class="btn btn-secondary"
                  @click="closeRenameFolderModal"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  class="btn btn-primary"
                  @click="renameFolder"
                >
                  Rename
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Upload File Modal -->
      <div
        v-if="showUploadFileModal"
        class="modal fade show centered-modal"
        tabindex="-1"
        role="dialog"
        aria-modal="true"
        style="display: block; background: rgba(0, 0, 0, 0.4)"
        @click.self="closeModal"
      >
        <div class="modal-dialog" role="document">
          <div class="modal-content shadow" @click.stop>
            <form
              @submit.prevent="submitFileUpload"
              enctype="multipart/form-data"
            >
              <div
                class="modal-header d-flex justify-content-between align-items-center"
              >
                <h5 class="modal-title">Upload File</h5>
                <button
                  type="button"
                  class="btn"
                  aria-label="Close"
                  @click="closeUploadFileModal"
                  style="
                    border: none;
                    background: transparent;
                    font-size: 1.25rem;
                  "
                >
                  <i class="bi bi-x-lg"></i>
                </button>
              </div>

              <div class="modal-body">
                <div class="mb-3">
                  <label for="fileInput" class="form-label">Choose File</label>
                  <input
                    type="file"
                    id="fileInput"
                    class="form-control"
                    accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                    @change="handleFileChange"
                    required
                  />
                </div>
                <div class="mb-3">
                  <label for="fileTitle" class="form-label">Title</label>
                  <input
                    type="text"
                    id="fileTitle"
                    class="form-control"
                    v-model="fileTitle"
                    required
                    placeholder="Enter file title"
                    autofocus
                  />
                </div>

                <div class="mb-3">
                  <label for="fileDescription" class="form-label"
                    >Description</label
                  >
                  <textarea
                    id="fileDescription"
                    class="form-control"
                    v-model="fileDescription"
                    rows="3"
                    placeholder="Enter file description (optional)"
                  ></textarea>
                </div>
              </div>

              <div class="modal-footer">
                <button
                  type="button"
                  class="btn btn-secondary"
                  @click="closeUploadFileModal"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  class="btn btn-primary"
                  @click="uploadFile"
                >
                  Upload
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Permission Modal -->
      <div
        v-if="showPermissionModal"
        class="modal d-block"
        tabindex="-1"
        role="dialog"
        @click.self="closePermissionModal"
        style="background-color: rgba(0, 0, 0, 0.5)"
      >
        <div
          class="modal-dialog modal-lg modal-dialog-centered"
          role="document"
          style="max-height: 60vh"
        >
          <div class="modal-content d-flex flex-column" style="height: 500px">
            <div class="modal-header">
              <h5 class="modal-title">
                Share {{ itemInfo.name || "Unnamed Item" }}
              </h5>
              <button
                type="button"
                class="btn-close"
                aria-label="Close"
                @click="closePermissionModal"
              ></button>
            </div>

            <div
              class="modal-body overflow-auto flex-grow-1"
              style="max-height: 450px"
            >
              <div
                class="d-flex align-items-center justify-content-between mb-3 border rounded p-2"
              >
                <span>{{ itemInfo.owner_email }}</span>
                <span class="badge bg-primary">Owner</span>
              </div>

              <div
                v-if="isPermissionsLoading"
                class="d-flex flex-column justify-content-center align-items-center text-primary"
                style="min-height: 300px"
              >
                <div class="spinner-border text-primary mb-2" role="status">
                  <span class="visually-hidden"></span>
                </div>
              </div>

              <div
                v-else-if="
                  permissionData.users.length === 0 &&
                  permissionData.teams.length === 0
                "
                class="d-flex flex-column justify-content-center align-items-center text-secondary"
                style="min-height: 200px"
              >
                <i class="bi bi-people-x fs-2 mb-2"></i>
                <p class="mb-0">No permissions assigned yet.</p>
              </div>

              <div v-else>
                <div>
                  <h6 class="fw-bold mt-3">Users</h6>
                  <div
                    v-for="(user, index) in permissionData.users"
                    :key="'user-' + user.user_id"
                    class="d-flex align-items-center justify-content-between mb-2 border rounded p-2"
                  >
                    <span>{{ user.email }}</span>
                    <select
                      v-model="permissionData.users[index].permission"
                      class="form-select form-select-sm w-auto"
                      @change="handleUserPermissionData(index)"
                      :disabled="user.email === currentUserEmail"
                    >
                      <option value="viewer">Viewer</option>
                      <option value="editor">Editor</option>
                      <option value="remove">Remove Access</option>
                    </select>
                  </div>
                </div>

                <div>
                  <h6 class="fw-bold mt-4">Teams</h6>
                  <div
                    v-for="(team, index) in permissionData.teams"
                    :key="'team-' + team.team_id"
                    class="d-flex align-items-center justify-content-between mb-2 border rounded p-2"
                  >
                    <span>{{ team.team_name }}</span>
                    <select
                      v-model="permissionData.teams[index].permission"
                      class="form-select form-select-sm w-auto"
                      @change="handleTeamPermissionData(index)"
                    >
                      <option value="viewer">Viewer</option>
                      <option value="editor">Editor</option>
                      <option value="remove">Remove Access</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>

            <div class="modal-footer">
              <button
                type="button"
                class="btn btn-secondary"
                @click="closePermissionModal"
              >
                Cancel
              </button>
              <button
                type="button"
                class="btn btn-primary"
                @click="savePermissions"
              >
                Save Changes
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Share to Person Modal -->
      <div
        v-if="showShareToPersonModal"
        class="modal fade show centered-modal"
        tabindex="-1"
        role="dialog"
        aria-modal="true"
        style="display: block; background: rgba(0, 0, 0, 0.4)"
        @click.self="closeModal"
      >
        <div class="modal-dialog" role="document">
          <div class="modal-content shadow" @click.stop>
            <form @submit.prevent="submitPermission">
              <div
                class="modal-header d-flex justify-content-between align-items-center"
              >
                <h5 class="modal-title">Share to Person</h5>
                <button
                  type="button"
                  class="btn"
                  aria-label="Close"
                  @click="closeShareToPersonModal"
                  style="
                    border: none;
                    background: transparent;
                    font-size: 1.25rem;
                  "
                >
                  <i class="bi bi-x-lg"></i>
                </button>
              </div>

              <div class="modal-body">
                <div class="mb-3">
                  <label for="email" class="form-label">User Email</label>
                  <input
                    type="email"
                    id="email"
                    class="form-control"
                    v-model="userEmail"
                    required
                    placeholder="Enter user email"
                    autofocus
                  />
                </div>

                <div class="mb-3">
                  <label for="folderPermission" class="form-label"
                    >Permission</label
                  >
                  <select
                    id="folderPermission"
                    class="form-select"
                    v-model="permission"
                    required
                  >
                    <option value="viewer">Viewer</option>
                    <option value="editor">Editor</option>
                  </select>
                </div>
              </div>

              <div class="modal-footer">
                <button
                  type="button"
                  class="btn btn-secondary"
                  @click="closeShareToPersonModal"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  class="btn btn-primary"
                  @click="handleShareToPersonClick"
                >
                  Share
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Share to Team Modal -->
      <div
        v-if="showShareToTeamModal"
        class="modal fade show centered-modal"
        tabindex="-1"
        role="dialog"
        aria-modal="true"
        style="display: block; background: rgba(0, 0, 0, 0.4)"
        @click.self="closeModal"
      >
        <div class="modal-dialog" role="document">
          <div class="modal-content shadow" @click.stop>
            <form @submit.prevent="submitTeamPermission">
              <div
                class="modal-header d-flex justify-content-between align-items-center"
              >
                <h5 class="modal-title">Share with your Team</h5>
                <button
                  type="button"
                  class="btn"
                  aria-label="Close"
                  @click="closeShareToTeamModal"
                  style="
                    border: none;
                    background: transparent;
                    font-size: 1.25rem;
                  "
                >
                  <i class="bi bi-x-lg"></i>
                </button>
              </div>

              <div class="modal-body">
                <div class="mb-3">
                  <label for="teamSelect" class="form-label">Select Team</label>
                  <select
                    id="teamSelect"
                    class="form-select"
                    v-model="selectedTeamId"
                    required
                  >
                    <option value="" disabled>Select a team</option>
                    <option
                      v-for="team in teams"
                      :key="team.id"
                      :value="team.id"
                    >
                      {{ team.team_name }}
                    </option>
                  </select>
                </div>

                <div class="mb-3">
                  <label for="teamPermission" class="form-label"
                    >Permission</label
                  >
                  <select
                    id="teamPermission"
                    class="form-select"
                    v-model="permission"
                    required
                  >
                    <option value="viewer">Viewer</option>
                    <option value="editor">Editor</option>
                  </select>
                </div>
              </div>

              <div class="modal-footer">
                <button
                  type="button"
                  class="btn btn-secondary"
                  @click="closeShareToTeamModal"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  class="btn btn-primary"
                  @click="handleShareWithTeamClick"
                >
                  Share
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- PDF Modal -->
      <div v-if="showPdfModal" class="custom-pdf-modal">
        <div class="custom-pdf-modal-content">
          <div class="pdf-header">
            <button class="close-btn" @click="closePdfModal" title="Close">
              &times;
            </button>
          </div>

          <iframe :src="pdfUrl" class="pdf-frame"></iframe>
        </div>
      </div>

      <!-- loading indicator -->
      <div
        v-if="isLoading"
        class="d-flex flex-column justify-content-center align-items-center text-primary"
        style="min-height: 60vh"
      >
        <div class="spinner-border text-primary mb-3" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
      </div>

      <!-- No Items Message -->
      <div
        v-else-if="folders.length === 0 && documents.length === 0"
        class="d-flex flex-column justify-content-center align-items-center text-gray-500"
        style="min-height: 60vh"
      >
        <svg
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          class="folder-empty-icon mb-3"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M3 7h4l2 3h10v11H3V7z"
          />
        </svg>
        <p class="fs-4 fw-semibold text-center">No shared files available</p>
      </div>

      <!-- Item List -->
      <div v-else class="folder-grid-container">
        <!-- Display Document Items -->
        <div
          class="folder-item text-center"
          v-for="(doc, index) in filteredDocuments"
          :key="'doc-' + doc.id"
        >
          <div
            class="position-absolute top-0 start-0 px-2 py-1 small rounded-bottom fw-bold"
            :class="{
              'text-primary': doc.file_type.toLowerCase() === 'docx',
              'text-danger': doc.file_type.toLowerCase() === 'pdf',
            }"
          >
            {{ doc.file_type }}
          </div>
          <div class="folder-dropdown position-absolute top-0 end-0 m-2">
            <button class="btn btn-sm" @click.stop="toggleDocumentMenu(index)">
              <i class="bi bi-three-dots-vertical"></i>
            </button>

            <div
              class="folder-menu"
              v-if="activeDocumentMenu === index"
              @click.stop
            >
              <div
                class="folder-menu-item"
                @click="viewFileDetailsModal(doc.id, doc.permission)"
                @mouseenter="shareSubMenuIndex = null"
              >
                View Details
              </div>

              <div
                class="folder-menu-item"
                v-if="
                  doc.permission === 'viewer' || doc.permission === 'editor'
                "
                @click="downloadFile(doc)"
                @mouseenter="shareSubMenuIndex = null"
              >
                Download
              </div>

              <template v-if="doc.permission === 'editor'">
                <div
                  class="folder-menu-item"
                  @click="
                    openPermissionModal(
                      doc.id,
                      doc.title,
                      'file',
                      doc.owner_email
                    )
                  "
                  @mouseenter="shareSubMenuIndex = null"
                >
                  Permissions
                </div>

                <div
                  class="folder-menu-item position-relative"
                  @click.stop="toggleShareSubMenu(index, 'document')"
                  @mouseenter="shareSubMenuIndex = index"
                >
                  Share
                  <div
                    class="folder-submenu"
                    v-if="
                      shareSubMenuIndex === index && shareType === 'document'
                    "
                    @click.stop
                    @mouseenter="shareSubMenuIndex = index"
                    @mouseleave="shareSubMenuIndex = null"
                  >
                    <div
                      class="folder-menu-item"
                      @click="toggleShowShareToPersonModal(doc.id, 'file')"
                    >
                      To a person
                    </div>
                    <div
                      class="folder-menu-item"
                      @click="toggleShareToTeam(doc.id, 'file', doc.title)"
                    >
                      With a team
                    </div>
                  </div>
                </div>
              </template>
            </div>
          </div>

          <!-- Document View -->
          <a
            @click="handleFileClick(doc)"
            class="text-decoration-none text-reset d-block pt-3"
          >
            <div class="position-relative">
              <i class="bi bi-file-earmark-text fs-1 text-secondary"></i>
            </div>
            <div class="folder-name text-muted small mt-2">
              {{ doc.file_name }}
            </div>
          </a>
        </div>

        <!-- Folder Items -->
        <div
          class="folder-item text-center"
          v-for="(folder, index) in filteredFolders"
          :key="'folder-' + folder.id"
        >
          <div class="folder-dropdown position-absolute top-0 end-0 m-2">
            <button class="btn btn-sm" @click.stop="toggleFolderMenu(index)">
              <i class="bi bi-three-dots-vertical"></i>
            </button>

            <div
              class="folder-menu"
              v-if="activeFolderMenu === index"
              @click.stop
            >
              <div
                class="folder-menu-item"
                v-if="folder.permission === 'viewer'"
                @click="goToTeamSharedFolder(folder)"
                style="cursor: pointer"
              >
                View
              </div>

              <template v-if="folder.permission === 'editor'">
                <div
                  class="folder-menu-item"
                  @click="openRenameModal(folder)"
                  @mouseenter="shareSubMenuIndex = null"
                >
                  Rename
                </div>
                <div
                  class="folder-menu-item"
                  @click="
                    openPermissionModal(
                      folder.id,
                      folder.folder_name,
                      'folder',
                      folder.owner_email
                    )
                  "
                  @mouseenter="shareSubMenuIndex = null"
                >
                  Permissions
                </div>

                <div
                  class="folder-menu-item position-relative"
                  @click.stop
                  @mouseenter="shareSubMenuIndex = index"
                >
                  Share
                  <div
                    class="folder-submenu"
                    v-if="shareSubMenuIndex === index && shareType === 'folder'"
                    @click.stop
                    @mouseenter="shareSubMenuIndex = index"
                    @mouseleave="shareSubMenuIndex = null"
                  >
                    <div
                      class="folder-menu-item"
                      @click="toggleShowShareToPersonModal(folder.id, 'file')"
                    >
                      To a person
                    </div>
                    <div
                      class="folder-menu-item"
                      @click="
                        toggleShareToTeam(
                          folder.id,
                          'folder',
                          folder.folder_name
                        )
                      "
                    >
                      With a team
                    </div>
                  </div>
                </div>
              </template>
            </div>
          </div>

          <router-link
            :to="`/shared/folder/contents/${folder.id}/${encodeURIComponent(
              folder.folder_name
            )}/${encodeURIComponent(folder.permission)}`"
            class="text-decoration-none text-reset d-block pt-3"
          >
            <div class="folder-icon mb-2">
              <i class="bi bi-folder-fill text-primary fs-1"></i>
            </div>
            <div class="folder-name text-muted small">
              {{ folder.folder_name }}
            </div>
          </router-link>
        </div>
      </div>
    </main>
  </div>
</template>

<script>
import axios from "axios";
export default {
  data() {
    return {
      // Modals
      showAccountMenu: false,
      activeFolderMenu: null,
      activeDocumentMenu: null,
      showNewFolderModal: false,
      showRenameFolderModal: false,
      shareSubMenuIndex: null,
      showShareToPersonModal: false,
      showShareToTeamModal: false,
      showAddBtnDropdown: false,
      isLoading: true,
      showUploadFileModal: false,
      showPermissionModal: false,
      showPdfModal: false,
      showUpdateAccountModal: false,
      showLogoutModal: false,
      loading: false,
      isPermissionsLoading: false,
      showViewDetailsModal: false,
      openFileUpdateModal: false,

      //Sidebar
      sidebarVisible: window.innerWidth >= 768,
      isDesktop: window.innerWidth >= 768,

      // Data Fields
      currentUserEmail: "",
      userFname: "",
      userLname: "",
      userEmail: "",
      searchQuery: "",
      newFolderName: "New Folder",
      renameFolderName: "",
      folder_id: "",
      document_id: "",
      permission: "",
      folderName: "",
      selectedTeamId: "",
      shareType: null,
      fileTitle: "",
      fileDescription: "",
      selectedFile: null,
      selectedFolderId: "",
      fileError: "",
      item_type: "",
      item_id: "",
      item_name: "",
      selectedDocument: null,
      pdfUrl: null,

      // Objects
      folders: [],
      documents: [],
      filteredDocuments: [],
      filteredFolders: [],
      teams: [],
      users: [],
      itemInfo: {
        item_id: "",
        item_type: "",
        name: "",
        owner_email: "",
      },
      permissionData: {
        users: [],
        teams: [],
      },
      form: {
        fname: "",
        lname: "",
        password: "",
        password_confirmation: "",
      },
    };
  },
  computed: {
    userInitial() {
      return this.currentUserEmail.charAt(0).toUpperCase();
    },
  },
  watch: {
    "$route.params.folder_id"(newVal, oldVal) {
      if (newVal !== oldVal) {
        this.filteredDocuments = [];
        this.filteredFolders = [];
        this.loadFolderContents();
      }
    },

    searchQuery(newVal) {
      const query = newVal.trim().toLowerCase();
      if (!query) {
        this.filteredFolders = this.folders;
        this.filteredDocuments = this.documents;
      } else {
        this.filteredFolders = this.folders.filter((folder) =>
          (folder.folder_name || "").toLowerCase().includes(query)
        );
        this.filteredDocuments = this.documents.filter((doc) =>
          (doc.file_name || "").toLowerCase().includes(query)
        );
      }
    },
  },
  mounted() {
    window.addEventListener("resize", this.handleResize);
    document.addEventListener("click", this.handleClickOutside);
    document.addEventListener("click", this.closeFolderMenus);
    document.addEventListener("click", this.closeDocumentMenus);
    this.setCredentials();
    this.loadFolderContents();
  },
  beforeUnmount() {
    window.removeEventListener("resize", this.handleResize);
    document.removeEventListener("click", this.handleClickOutside);
    document.removeEventListener("click", this.handleClickOutside);
    document.removeEventListener("click", this.closeFolderMenus);
    document.removeEventListener("click", this.closeDocumentMenus);
  },
  methods: {
    // handle logout
    openLogoutModal() {
      this.showLogoutModal = true;
    },
    closeLogoutModal() {
      this.showLogoutModal = false;
    },
    logout() {
      localStorage.removeItem("token");
      localStorage.removeItem("user_id");
      localStorage.removeItem("fname");
      localStorage.removeItem("lname");
      localStorage.removeItem("email");
      this.$router.push("/");
    },

    // manage account
    openUpdateAccountModal() {
      this.form.fname = this.userFname || "";
      this.form.lname = this.userLname || "";
      this.form.password = "";
      this.form.password_confirmation = "";
      this.successMessage = "";
      this.errorMessage = "";
      this.showUpdateAccountModal = true;
    },
    closeUpdateAccountModal() {
      this.showUpdateAccountModal = false;
    },
    updateAccount() {
      this.loading = true;
      this.successMessage = "";
      this.errorMessage = "";

      const token = localStorage.getItem("token");
      const user_id = localStorage.getItem("user_id");

      const formData = {
        ...this.form,
        user_id: user_id,
      };

      axios
        .post(`${process.env.VUE_APP_API_URL}/updateAccount`, formData, {
          headers: {
            Authorization: `Bearer ${token}`,
          },
        })
        .then((response) => {
          this.successMessage = response.data.message;
          this.setCredentials();
        })
        .catch((error) => {
          if (error.response?.data?.errors) {
            this.errorMessage = Object.values(error.response.data.errors).join(
              " "
            );
          } else {
            this.errorMessage = "Failed to update account.";
          }
        })
        .finally(() => {
          this.loading = false;
        });
    },

    // functions to handle file update
    async viewFileDetailsModal(doc_id, permission) {
      const token = localStorage.getItem("token");
      await axios
        .get(`${process.env.VUE_APP_API_URL}/view/file/${doc_id}`, {
          headers: {
            Authorization: `Bearer ${token}`,
          },
        })
        .then((response) => {
          const document = response.data;
          this.file_form = {
            id: document.id,
            title: document.title,
            description: document.description,
            file_name: document.file_name,
            file_type: document.file_type,
          };

          this.selectedFile = null;
          this.fileError = "";

          if (permission == "editor") {
            this.openFileUpdateModal = true;
          } else {
            this.showViewDetailsModal = true;
          }
        })
        .catch((error) => {
          console.error("Error fetching document details:", error);
          alert("Failed to load document details.");
        });
    },

    closeFileDetailsModal() {
      this.showViewDetailsModal = false;
      this.openFileUpdateModal = false;
      this.file_form = { id: null, title: "", description: "" };
      this.selectedFile = null;
      this.fileError = "";
    },
    async updateDocument() {
      const token = localStorage.getItem("token");
      const formData = new FormData();

      formData.append("title", this.file_form.title);
      formData.append("description", this.file_form.description || "");
      if (this.selectedFile) {
        formData.append("file", this.selectedFile);
      }

      await axios
        .post(
          `${process.env.VUE_APP_API_URL}/update/file/${this.file_form.id}`,
          formData,
          {
            headers: {
              Authorization: `Bearer ${token}`,
              "Content-Type": "multipart/form-data",
            },
          }
        )
        .then((response) => {
          if (response) {
            this.loadFolderContents();
            this.closeFileDetailsModal();
          }
        })
        .catch((error) => {
          console.error("Update error", error);
          alert("Failed to update the document. Please try again.");
        });
    },

    // handle file viewing
    handleFileClick(doc) {
      if (doc.file_type.toLowerCase() === "docx") {
        this.downloadFile(doc);
      } else if (doc.file_type.toLowerCase() === "pdf") {
        this.openPdfModal(doc);
      } else {
        alert("Unsupported file type");
      }
    },
    openPdfModal(doc) {
      this.selectedDocument = doc;
      this.showPdfModal = true;
      this.fetchPdf(doc.id);
    },

    closePdfModal() {
      this.showPdfModal = false;
      this.selectedDocument = null;
    },
    async fetchPdf(docId) {
      const token = localStorage.getItem("token");
      await axios
        .get(`${process.env.VUE_APP_API_URL}/pdf/view/${docId}`, {
          responseType: "blob",
          headers: {
            Authorization: `Bearer ${token}`,
          },
        })
        .then((response) => {
          this.pdfUrl = URL.createObjectURL(response.data);
        })
        .catch((err) => {
          this.error = "Failed to load PDF";
          console.error(err);
        });
    },

    async downloadFile(doc) {
      const token = localStorage.getItem("token");
      const docx_id = doc.id;

      await axios
        .get(`${process.env.VUE_APP_API_URL}/docx/download/${docx_id}`, {
          headers: {
            Authorization: `Bearer ${token}`,
          },
          responseType: "blob",
        })
        .then((response) => {
          const disposition = response.headers["content-disposition"];
          let filename = "document.docx"; // fallback

          // Try to extract filename from headers
          if (disposition && disposition.indexOf("filename=") !== -1) {
            filename = disposition
              .split("filename=")[1]
              .replace(/['"]/g, "")
              .trim();
          }

          const blob = new Blob([response.data], {
            type: "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
          });

          const link = document.createElement("a");
          link.href = window.URL.createObjectURL(blob);
          link.download = filename;
          link.click();

          window.URL.revokeObjectURL(link.href);
        })
        .catch((error) => {
          console.error("Failed to download DOCX:", error);
          alert("Download failed.");
        });
    },
    // Toggle Modals
    toggleAccountMenu() {
      this.showAccountMenu = !this.showAccountMenu;
      document.addEventListener("click", this.handleClickOutside);
    },
    showDropdownAddBtn() {
      this.showAddBtnDropdown = !this.showAddBtnDropdown;
    },
    addFolder() {
      this.hideDropdown();
      this.toggleAddFolderModal();
    },
    addDocument() {
      this.showUploadFileModal = true;
      this.fileTitle = "";
      this.fileDescription = "";
      this.selectedFile = null;
      this.selectedFolderId = "";
      document.addEventListener("click", this.handleClickOutside);
      this.hideDropdown();
      this.showUploadFileModal = true;
    },
    toggleAddFolderModal() {
      this.showNewFolderModal = true;
      this.newFolderName = "";
      document.addEventListener("click", this.handleClickOutside);
    },
    toggleFolderMenu(index) {
      this.activeFolderMenu = this.activeFolderMenu === index ? null : index;
      this.activeDocumentMenu = null;
      this.shareSubMenuIndex = null;
      this.shareType = "folder";
    },
    toggleDocumentMenu(index) {
      this.activeDocumentMenu =
        this.activeDocumentMenu === index ? null : index;
      this.activeFolderMenu = null;
      this.shareSubMenuIndex = null;
      this.shareType = "document";
    },
    async openPermissionModal(item_id, item_name, item_type, owner_email) {
      this.activeDocumentMenu = null;
      this.shareSubMenuIndex = null;
      this.itemInfo.name = item_name;
      this.itemInfo.owner_email = owner_email;
      this.showPermissionModal = true;
      this.isPermissionsLoading = true;

      await Promise.all([
        this.getUserPermissions(item_id, item_type),
        this.getTeamPermissions(item_id, item_type),
      ]);
      this.isPermissionsLoading = false;
    },
    toggleShareSubMenu(index, type) {
      this.shareSubMenuIndex = this.shareSubMenuIndex === index ? null : index;
      this.shareType = type;
    },
    openRenameModal(folder) {
      this.renameFolderName = folder.folder_name;
      this.folder_id = folder.id;
      this.showRenameFolderModal = true;
      this.activeFolderMenu = null;
      document.addEventListener("click", this.handleClickOutside);
    },
    toggleShowShareToPersonModal(item_id, item_type) {
      this.item_type = item_type;
      this.activeDocumentMenu = null;
      this.shareSubMenuIndex = null;
      this.showShareToPersonModal = true;
      if (item_type == "file") {
        this.document_id = item_id;
      } else {
        this.folder_id = item_id;
      }

      this.userEmail = "";
    },
    handleShareToPersonClick() {
      if (this.item_type === "folder") {
        this.shareFolderToPerson();
      } else if (this.item_type === "file") {
        this.shareFileToPerson();
      }
    },
    toggleShareToTeam(item_id, item_type, item_name) {
      this.item_type = item_type;
      this.activeDocumentMenu = null;
      this.shareSubMenuIndex = null;
      this.item_name = item_name;
      this.selectedTeamId = "";
      this.showShareToTeamModal = true;
      this.getUserTeams();
      if (item_type == "file") {
        this.document_id = item_id;
      } else {
        this.folder_id = item_id;
      }
    },
    handleShareWithTeamClick() {
      if (this.item_type === "folder") {
        this.shareFolderToTeam();
      } else if (this.item_type === "file") {
        this.shareFileToTeam();
      }
    },
    showErrorModal(message) {
      const modal = document.getElementById("errorModal");
      const closeBtn = document.getElementById("closeModalBtn");
      const errorMessage = document.getElementById("errorMessage");

      errorMessage.textContent = message;
      modal.classList.remove("hidden");

      closeBtn.addEventListener("click", () => {
        modal.classList.add("hidden");
      });
    },
    handleClickOutsideAddBtn(event) {
      const dropdown = this.$refs.dropdown;
      if (dropdown && !dropdown.contains(event.target)) {
        this.hideDropdown();
      }
    },
    closeDocumentMenus(e) {
      if (!e.target.closest(".folder-dropdown")) {
        this.activeDocumentMenu = null;
        this.shareSubMenuIndex = null;
      }
    },
    handleClickOutside(event) {
      const menu = this.$refs.accountMenu;
      const icon = this.$refs.accountIcon;
      const modal = this.$refs.newFolderModal;
      const btn = this.$refs.newFolderBtn;

      if (
        menu &&
        icon &&
        !menu.contains(event.target) &&
        !icon.contains(event.target)
      ) {
        this.showAccountMenu = false;
      }

      if (
        modal &&
        !modal.contains(event.target) &&
        btn &&
        !btn.contains(event.target)
      ) {
        this.closeNewFolderModal();
      }

      if (!this.toggleAccountMenu && !this.showNewFolderModal) {
        document.removeEventListener("click", this.handleClickOutside);
      }
    },
    closePermissionModal() {
      this.showPermissionModal = false;
      this.permissionData = { users: [], teams: [] };
    },
    //Close Modals
    hideDropdown() {
      this.showDropdown = false;
    },
    closeNewFolderModal() {
      this.showNewFolderModal = false;
      document.removeEventListener("click", this.handleOutsideClickNewFolder);
    },
    closeFolderMenus(e) {
      if (!e.target.closest(".folder-dropdown")) {
        this.activeFolderMenu = null;
      }
    },
    closeRenameFolderModal() {
      this.showRenameFolderModal = false;
      document.removeEventListener(
        "click",
        this.handleOutsideClickRenameFolder
      );
    },
    closeShareToPersonModal() {
      this.showShareToPersonModal = false;
      this.userEmail = "";
      document.removeEventListener(
        "click",
        this.handleOutsideClickShareToPerson
      );
    },
    closeShareToTeamModal() {
      this.showShareToTeamModal = false;
      this.selectedTeamId = "";
      this.permission = "viewer";
    },
    closeErrorModal() {
      const errorModal = document.getElementById("errorModal");
      if (errorModal) {
        errorModal.classList.add("hidden");
      }
    },
    closeUploadFileModal() {
      this.showUploadFileModal = false;
      this.fileTitle = "";
      this.fileDescription = "";
      this.selectedFile = null;
      this.selectedFolderId = "";
      this.showAddBtnDropdown = false;
    },

    toggleSidebar() {
      this.sidebarVisible = !this.sidebarVisible;
    },
    handleResize() {
      this.isDesktop = window.innerWidth >= 768;
      this.sidebarVisible = this.isDesktop;
    },
    navigateTo(route) {
      this.$router.push(route);
    },
    setCredentials() {
      const user_id = localStorage.getItem("user_id");
      const token = localStorage.getItem("token");

      if (!user_id) {
        console.error("User ID not found in localStorage.");
        return;
      }

      if (!token) {
        console.error("Auth token not found in localStorage.");
        return;
      }

      axios
        .post(
          `${process.env.VUE_APP_API_URL}/getUserInfo`,
          { user_id: user_id },
          {
            headers: {
              Authorization: `Bearer ${token}`,
            },
          }
        )
        .then((response) => {
          const user = response.data;
          this.userFname = user.first_name;
          this.userLname = user.last_name;
          this.currentUserEmail = user.email;
        })
        .catch((error) => {
          console.error("Failed to fetch user info:", error);
        });
    },

    // Methods For Folder CRUD
    async loadFolderContents() {
      this.isLoading = true;
      const folder_id = this.$route.params.folder_id;

      try {
        if (folder_id) {
          const [folders, documents] = await Promise.all([
            this.fetchFolders(folder_id),
            this.fetchDocuments(folder_id),
          ]);

          this.folders = folders;
          this.documents = documents;

          this.filteredFolders = [...folders];
          this.filteredDocuments = [...documents];
        } else {
          this.folders = [];
          this.documents = [];
        }
      } catch (error) {
        console.error("Error loading content:", error);
      } finally {
        this.isLoading = false;
      }
    },

    async fetchFolders(folder_id) {
      const token = localStorage.getItem("token");
      const user_id = localStorage.getItem("user_id");

      try {
        const response = await axios.post(
          `${process.env.VUE_APP_API_URL}/getSharedSubfolders`,
          { user_id, folder_id },
          {
            headers: { Authorization: `Bearer ${token}` },
          }
        );
        return response.data.subfolders || [];
      } catch (error) {
        console.error("Error fetching subfolders:", error);
        return [];
      }
    },

    async fetchDocuments(folder_id) {
      const token = localStorage.getItem("token");
      const user_id = localStorage.getItem("user_id");

      try {
        const response = await axios.post(
          `${process.env.VUE_APP_API_URL}/getSharedFolderFiles`,
          { user_id, folder_id },
          {
            headers: { Authorization: `Bearer ${token}` },
          }
        );
        return response.data.documents || [];
      } catch (error) {
        console.error("Error fetching documents:", error);
        return [];
      }
    },

    async createNewFolder() {
      const token = localStorage.getItem("token");
      const user_id = localStorage.getItem("user_id");
      const folder_id = this.$route.params.folder_id || "";
      await axios
        .post(
          `${process.env.VUE_APP_API_URL}/createFolder`,
          {
            user_id: user_id,
            folder_name: this.newFolderName,
            parent_id: folder_id,
          },
          {
            headers: {
              Authorization: `Bearer ${token}`,
            },
          }
        )
        .then((response) => {
          if (response.data.response === "success") {
            this.loadFolderContents();
            this.closeNewFolderModal();
          }
        })
        .catch((error) => {
          console.error("Error fetching folders:", error);
        });
    },

    async renameFolder() {
      this.filteredDocuments = [];
      this.filteredFolders = [];
      const token = localStorage.getItem("token");
      const user_id = localStorage.getItem("user_id");

      await axios
        .put(
          `${process.env.VUE_APP_API_URL}/renameFolder`,
          {
            user_id: user_id,
            folder_id: this.folder_id,
            folder_name: this.renameFolderName,
          },
          {
            headers: {
              Authorization: `Bearer ${token}`,
            },
          }
        )
        .then((response) => {
          if (response.data.response === "success") {
            this.renameFolderName = "";
            this.folder_id = "";
            this.loadFolderContents();
            this.closeRenameFolderModal();
          } else {
            alert("Failed to rename folder. Please try again.");
          }
        })
        .catch((error) => {
          console.error("Error fetching folders:", error);
        });
    },

    async shareFolderToPerson() {
      const token = localStorage.getItem("token");
      const user_id = localStorage.getItem("user_id");
      await axios
        .post(
          `${process.env.VUE_APP_API_URL}/shareFolderWithPerson/${this.folder_id}`,
          {
            email: this.userEmail,
            owner_id: user_id,
            permission: this.permission,
          },
          {
            headers: {
              Authorization: `Bearer ${token}`,
            },
          }
        )
        .then((response) => {
          const res = response.data.response;

          if (res === "success") {
            this.showErrorModal(`Successfully shared With ${this.userEmail}.`);
          } else if (res === "already_shared") {
            this.showErrorModal(
              `Sorry, you cannot share with ${this.userEmail} because they already have access to this folder.`
            );
          } else if (res === "owner") {
            this.showErrorModal(
              `Oops! You can't share a folder with yourself.`
            );
          } else {
            this.showErrorModal(
              "An error occurred while sharing the folder. Please try again."
            );
          }

          // Reset
          this.userEmail = "";
          this.permission = "";
          this.folder_id = "";
          this.activeFolderMenu = null;
          this.closeShareToPersonModal();
        })
        .catch((error) => {
          if (
            error.response &&
            error.response.status === 404 &&
            error.response.data.response === "unregistered"
          ) {
            this.showErrorModal(
              `Sorry, you cannot share with ${this.userEmail} because they do not have a registered account.`
            );
          } else {
            this.showErrorModal(
              "An unexpected error occurred. Please try again."
            );
          }
        });
    },
    async shareFileToPerson() {
      const token = localStorage.getItem("token");
      const user_id = localStorage.getItem("user_id");
      await axios
        .post(
          `${process.env.VUE_APP_API_URL}/shareFileToPerson`,
          {
            document_id: this.document_id,
            email: this.userEmail,
            granted_by: user_id,
            permission: this.permission,
          },
          {
            headers: {
              Authorization: `Bearer ${token}`,
            },
          }
        )
        .then((response) => {
          const res = response.data.response;

          if (res === "success") {
            this.showErrorModal(`Successfully shared With ${this.userEmail}.`);
          } else if (res === "already_shared") {
            this.showErrorModal(
              `Sorry, you cannot share with ${this.userEmail} because they already have access to this file.`
            );
          } else if (res == "not_registered") {
            this.showErrorModal(`Sorry, ${this.userEmail} is not registered.`);
          } else if (res === "owner") {
            this.showErrorModal(`Oops! You can't share a file with yourself.`);
          } else {
            this.showErrorModal(
              "An error occurred while sharing the file. Please try again."
            );
          }

          // Reset
          this.userEmail = "";
          this.permission = "";
          this.folder_id = "";
          this.activeFolderMenu = null;
          this.closeShareToPersonModal();
        })
        .catch((error) => {
          if (
            error.response &&
            error.response.status === 404 &&
            error.response.data.response === "unregistered"
          ) {
            this.showErrorModal(
              `Sorry, you cannot share with ${this.userEmail} because they do not have a registered account.`
            );
          } else {
            this.showErrorModal(
              "An unexpected error occurred. Please try again."
            );
          }
        });
    },

    async shareFileToTeam() {
      const token = localStorage.getItem("token");
      const user_id = localStorage.getItem("user_id");

      await axios
        .post(
          `${process.env.VUE_APP_API_URL}/shareFileToTeam`,
          {
            document_id: this.document_id,
            team_id: this.selectedTeamId,
            granted_by: user_id,
            permission: this.permission,
          },
          {
            headers: {
              Authorization: `Bearer ${token}`,
            },
          }
        )
        .then((response) => {
          if (response.data.response === "success") {
            this.showErrorModal(`File shared successfully with your team.`);
          } else if (response.data.response === "already_shared") {
            this.showErrorModal("This file is already shared with your team.");
          } else {
            this.showErrorModal(
              "An error occurred while sharing the file. Please try again."
            );
          }
          this.selectedTeamId = "";
          this.permission = "";
          this.folderName = "";
          this.activeFolderMenu = null;
          this.closeShareToTeamModal();
        })
        .catch((error) => {
          console.error("Error sharing file with team:", error);
          alert("Failed to share file with team");
        });
    },

    async getUserTeams() {
      const token = localStorage.getItem("token");
      const user_id = localStorage.getItem("user_id");

      await axios
        .post(
          `${process.env.VUE_APP_API_URL}/getUserTeams`,
          { user_id: user_id },
          {
            headers: {
              Authorization: `Bearer ${token}`,
            },
          }
        )
        .then((response) => {
          if (response.data.response === "success") {
            this.teams = response.data.teams || [];
          } else {
            this.showErrorModal("Failed to fetch teams. Please try again.");
          }
        })
        .catch((error) => {
          console.error("Error fetching user teams:", error);
        });
    },

    async shareFolderToTeam() {
      const token = localStorage.getItem("token");
      const user_id = localStorage.getItem("user_id");

      await axios
        .post(
          `${process.env.VUE_APP_API_URL}/shareFolderWithTeam/${this.folder_id}`,
          {
            granted_by: user_id,
            team_id: this.selectedTeamId,
            permission: this.permission,
          },
          {
            headers: {
              Authorization: `Bearer ${token}`,
            },
          }
        )
        .then((response) => {
          if (response.data.response === "success") {
            this.showErrorModal(`Folder shared successfully with the team.`);
          } else if (response.data.response === "already_shared") {
            this.showErrorModal(
              "This folder is already shared with your team."
            );
          } else {
            this.showErrorModal(
              "An error occurred while sharing the folder. Please try again."
            );
          }
          this.selectedTeamId = "";
          this.permission = "";
          this.folderName = "";
          this.activeFolderMenu = null;
          this.closeShareToTeamModal();
        })
        .catch((error) => {
          console.error("Error sharing folder with team:", error);
          alert("Failed to share folder with team");
        });
    },

    handleFileChange(event) {
      const file = event.target.files[0];
      if (!file) return;

      const allowedTypes = [
        "application/pdf",
        "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
      ];

      if (!allowedTypes.includes(file.type)) {
        this.fileError = "Only PDF and DOCX files are allowed.";
        this.selectedFile = null;
        event.target.value = ""; // Clear the input
      } else {
        this.fileError = "";
        this.selectedFile = file;
      }
    },
    uploadFile() {
      const token = localStorage.getItem("token");
      const user_id = localStorage.getItem("user_id");
      const folder_id = this.$route.params.folder_id || null;

      if (!this.selectedFile) {
        console.error("No file selected");
        return;
      }

      const formData = new FormData();
      formData.append("uploaded_by", user_id);
      formData.append("title", this.fileTitle);
      formData.append("description", this.fileDescription || "");
      formData.append("file", this.selectedFile);
      if (folder_id) {
        formData.append("folder_id", folder_id);
      }

      axios
        .post(`${process.env.VUE_APP_API_URL}/uploadFile`, formData, {
          headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "multipart/form-data",
          },
        })
        .then((response) => {
          if (response) {
            this.loadFolderContents();
            this.closeUploadFileModal();
            this.showAddBtnDropdown = false;
          }
        })
        .catch((error) => {
          if (error) {
            console.log("error");
          }
        });
    },

    async moveFileToTrash(document) {
      this.isLoading = true;
      const token = localStorage.getItem("token");

      await axios
        .delete(
          `${process.env.VUE_APP_API_URL}/softDeleteFile/${document.id}`,
          {
            headers: {
              Authorization: `Bearer ${token}`,
            },
          }
        )
        .then((response) => {
          if (response.data.response === "success") {
            this.activeFolderMenu = null;
            this.loadFolderContents();
          } else {
            this.showErrorModal("Something went wrong.Please try again.");
          }
        })
        .catch((error) => {
          console.error("Error fetching user teams:", error);
        });
    },

    async getUserPermissions(item_id, item_type) {
      const token = localStorage.getItem("token");

      const key = item_type === "folder" ? "folder_id" : "document_id";

      await axios
        .post(
          `${process.env.VUE_APP_API_URL}/permissions/user`,
          {
            [key]: item_id,
          },
          {
            headers: {
              Authorization: `Bearer ${token}`,
            },
          }
        )
        .then((response) => {
          const userPermissions = response.data;

          this.permissionData.users = userPermissions.map((permission) => ({
            id: permission.user_id,
            email: permission.email,
            permission: permission.permission,
            permissionable_id: permission.permissionable_id,
            permissionable_type: permission.permissionable_type,
          }));
        })
        .catch((error) => {
          console.error("Error:", error.response?.data || error.message);
        });
    },

    async getTeamPermissions(item_id, item_type) {
      const token = localStorage.getItem("token");
      const key = item_type === "folder" ? "folder_id" : "document_id";

      await axios
        .post(
          `${process.env.VUE_APP_API_URL}/permissions/teams`,
          {
            [key]: item_id,
          },
          {
            headers: {
              Authorization: `Bearer ${token}`,
            },
          }
        )
        .then((response) => {
          const teamPermissions = response.data;

          this.permissionData.teams = teamPermissions.map((permission) => ({
            team_id: permission.team_id,
            team_name: permission.team_name,
            permission: permission.permission,
            item_id: permission.item_id,
            item_type: permission.item_type,
          }));
        })
        .catch((error) => {
          console.error("Error:", error.response?.data || error.message);
        });
    },

    handleUserPermissionData(index) {
      const user = this.permissionData.users[index];
      this.itemInfo.item_id = user.permissionable_id;
      this.itemInfo.item_type = user.permissionable_type;
      const updated = {
        user_id: user.user_id || user.id,
        permission: user.permission,
      };

      const existingIndex = this.users.findIndex(
        (u) => u.user_id === updated.user_id
      );

      if (existingIndex !== -1) {
        if (this.users[existingIndex].permission !== updated.permission) {
          this.users[existingIndex].permission = updated.permission;
        }
      } else {
        this.users.push(updated);
      }
    },
    handleTeamPermissionData(index) {
      const team = this.permissionData.teams[index];
      this.itemInfo.item_id = team.item_id;
      this.itemInfo.item_type = team.item_type;
      const updated = {
        team_id: team.team_id,
        permission: team.permission,
      };

      const existingIndex = this.teams.findIndex(
        (t) => t.team_id === updated.team_id
      );

      if (existingIndex !== -1) {
        if (this.teams[existingIndex].permission !== updated.permission) {
          this.teams[existingIndex].permission = updated.permission;
        }
      } else {
        this.teams.push(updated);
      }
    },
    savePermissions() {
      this.filteredDocuments = [];
      this.filteredFolders = [];
      if (this.users.length === 0 && this.teams.length === 0) {
        this.closePermissionModal();
        return;
      }

      this.closePermissionModal();
      this.isLoading = true;

      const token = localStorage.getItem("token");
      const user_id = localStorage.getItem("user_id");
      const item_id = this.itemInfo.item_id;
      const item_type = this.itemInfo.item_type;

      const requests = [];

      if (this.users.length > 0) {
        const userPermissionsData = {
          permissionable_type: item_type,
          permissionable_id: item_id,
          granted_by: user_id,
          users: this.users.map((user) => ({
            user_id: user.user_id,
            permission: user.permission,
          })),
        };

        requests.push(
          axios.post(
            `${process.env.VUE_APP_API_URL}/permissions/update`,
            userPermissionsData,
            {
              headers: {
                "Content-Type": "application/json",
                Authorization: `Bearer ${token}`,
              },
            }
          )
        );
      }

      if (this.teams.length > 0) {
        const teamPermissionsData = {
          item_type: item_type,
          item_id: item_id,
          granted_by: user_id,
          teams: this.teams.map((team) => ({
            team_id: team.team_id,
            permission: team.permission,
          })),
        };

        requests.push(
          axios.post(
            `${process.env.VUE_APP_API_URL}/teamPermissions/update`,
            teamPermissionsData,
            {
              headers: {
                Authorization: `Bearer ${token}`,
              },
            }
          )
        );
      }

      Promise.all(requests)
        .then((responses) => {
          const allSuccess = responses.every(
            (res) => res.data.response === "success"
          );

          if (allSuccess) {
            this.showErrorModal("Permissions updated successfully.");
          } else {
            this.showErrorModal("Something went wrong. Please try again.");
          }
          this.loadFolderContents();
        })
        .catch((error) => {
          if (error.response && error.response.status === 422) {
            const validationErrors = error.response.data.errors;
            let message = "Validation failed:\n";
            for (const key in validationErrors) {
              message += `${key}: ${validationErrors[key].join(", ")}\n`;
            }
            this.showErrorModal(message);
          } else {
            this.showErrorModal("Something went wrong. Please try again.");
          }
        });
    },
  },
};
</script>

<style scoped>
.custom-pdf-modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(0, 0, 0, 0.95);
  z-index: 1055;
  display: flex;
  flex-direction: column;
}

.custom-pdf-modal-content {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
}

.pdf-header {
  display: flex;
  align-items: center;
  padding: 12px 20px;
  background-color: #202124;
  color: #fff;
  height: 50px;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.6);
}

.close-btn {
  font-size: 1.8rem;
  color: white;
  background: none;
  border: none;
  margin-right: 16px;
  cursor: pointer;
}

.menu-icon {
  font-size: 1.5rem;
  margin-right: 20px;
  cursor: pointer;
}

.doc-title {
  font-size: 1rem;
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.pdf-frame {
  flex: 1;
  width: 100%;
  border: none;
}

.dashboard {
  position: relative;
  min-height: 100vh;
  background-color: #f8f9fa;
}

.topbar {
  height: 60px;
  background-color: white;
  position: fixed;
  width: 100%;
  top: 0;
  left: 0;
  z-index: 1001;
}

.topbar .search-wrapper {
  flex-grow: 1;
  max-width: 600px;
}

.dashboard {
  position: relative;
  min-height: 100vh;
  background-color: #f8f9fa;
}

.topbar {
  height: 60px;
  background-color: white;
  position: fixed;
  width: 100%;
  top: 0;
  left: 0;
  z-index: 1001;
}

.topbar .search-wrapper {
  flex-grow: 1;
  max-width: 600px;
}

.sidebar {
  width: 250px;
  height: 100vh;
  position: fixed;
  left: 0;
  z-index: 1000;
  overflow-y: auto;
  transition: transform 0.3s ease;
}

.slide-enter-active,
.slide-leave-active {
  transition: transform 0.3s ease;
}
.slide-enter-from,
.slide-leave-to {
  transform: translateX(-100%);
}

.main-content {
  padding-top: 80px;
  padding-left: 1rem;
  padding-right: 1rem;
}
.main-content.with-sidebar {
  margin-left: 250px;
}

/* No Folder Div */
.text-gray-500 {
  color: #6c757d;
}
.text-3xl {
  font-size: 1.75rem;
}

.folder-empty-icon {
  width: 200px;
  height: 200px;
  color: #adb5bd;
}

.account-icon {
  width: 40px;
  height: 40px;
  font-weight: bold;
  font-size: 18px;
  cursor: pointer;
}

.account-menu {
  background-color: #fff;
  border: 1px solid #ddd;
  border-radius: 10px;
  padding: 16px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
  position: absolute;
  right: 1rem;
  top: 4rem;
  width: 400px;
  z-index: 1100;
}

.account-circle {
  width: 60px;
  height: 60px;
  background-color: #e1ecff;
  border-radius: 50%;
  display: flex;
  justify-content: center;
  align-items: center;
  font-size: 24px;
  margin: auto;
  font-weight: bold;
  color: #0056b3;
}

.nav-link {
  color: #333;
  padding: 10px 16px;
  border-radius: 8px;
  transition: background-color 0.2s ease;
}
.nav-link:hover {
  background-color: #eaf3ff;
  color: #0066cc;
}

.main-content .card {
  background-color: #f8f9fa;
  height: 100vh;
}
/* Folders */
.folder-grid-container {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 20px;
}

.folder-item {
  position: relative;
  padding: 1rem;
  background-color: #fff;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  transition: box-shadow 0.3s ease;
}

.folder-item:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.folder-icon {
  font-size: 2.5rem;
}

.folder-name {
  font-weight: 600;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* Dropdown menu inside folder */
.folder-dropdown {
  position: absolute;
  top: 10px;
  right: 10px;
}

.folder-menu {
  position: absolute;
  top: 30px;
  right: 0;
  background: white;
  border: 1px solid #dee2e6;
  border-radius: 5px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  z-index: 10;
}

.folder-menu-item {
  padding: 8px 12px;
  cursor: pointer;
  white-space: nowrap;
}

.folder-menu-item:hover {
  background-color: #f1f1f1;
}

.folder-submenu {
  position: absolute;
  top: 0;
  left: 100%;
  margin-left: 0.5rem;
  background: #fff;
  border-radius: 0.25rem;
  box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.1);
  min-width: 180px;
  z-index: 1000;
}

.folder-submenu.left-align {
  left: auto !important;
  right: 100%;
  margin-left: 0;
  margin-right: 0.5rem;
}

.folder-menu-item {
  padding: 0.5rem 1rem;
  cursor: pointer;
  white-space: nowrap;
}

.folder-menu-item:hover {
  background-color: #f1f1f1;
}

/* Add Folder Modal */
.centered-modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  display: flex !important;
  align-items: center;
  justify-content: center;
  z-index: 1050;
  background-color: rgba(0, 0, 0, 0.4);
}

.modal-dialog {
  max-width: 400px;
  width: 100%;
}
.modal-content {
  border-radius: 12px;
  overflow: hidden;
}

.modal-header {
  background-color: #f8f9fa;
  border-bottom: 1px solid #dee2e6;
}

.modal-footer {
  background-color: #f8f9fa;
  border-top: 1px solid #dee2e6;
}

.modal-title {
  font-weight: 600;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.2rem;
}

/* Error Modal */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background-color: rgba(0, 0, 0, 0.3);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
}

.modal-box {
  background-color: white;
  border-radius: 8px;
  padding: 20px 30px;
  max-width: 400px;
  width: 90%;
  min-height: 150px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.modal-message-container {
  min-height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal-box p {
  font-size: 16px;
  text-align: center;
  margin: 0;
}

.modal-box button {
  padding: 6px 20px;
  font-size: 14px;
  background-color: #1a73e8;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  align-self: center;
  margin-top: 20px;
}

.modal-box button:hover {
  background-color: #1669c1;
}

.hidden {
  display: none;
}

/* Perrmission Modal */
.modal-body {
  scrollbar-width: thin;
  scrollbar-color: #cccccc #f8f9fa;
}

.modal-body::-webkit-scrollbar {
  width: 6px;
}
.modal-body::-webkit-scrollbar-thumb {
  background-color: #cccccc;
  border-radius: 4px;
}
.modal-body::-webkit-scrollbar-track {
  background: #f8f9fa;
}

@media (max-width: 991px) {
  .folder-item {
    width: calc(100% / 3 - 0.8rem);
  }
}

@media (max-width: 576px) {
  .folder-item {
    width: calc(100% / 2 - 0.8rem);
  }
}

@media (min-width: 768px) {
  .topbar {
    left: 260px;
    width: calc(100% - 260px);
  }
  .sidebar {
    top: 0;
  }
}
</style>
