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
            @input="performSearch"
            class="form-control border-start-0"
            placeholder="Search folders..."
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
        <h4 class="fw-bold text-primary mb-0">My Folders</h4>
        <button class="btn btn-outline-primary" @click="toggleAddFolderModal">
          <i class="bi bi-folder-plus me-1"></i> New Folder
        </button>
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
                  {{ loading ? "Updating..." : "Save Changes" }}
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
                  @click="shareFolderToPerson"
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
                <h5 class="modal-title">Share "{{ folderName }}" to Team</h5>
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
                  @click="shareFolderToTeam"
                >
                  Share
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
                v-if="isLoading"
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

            <!-- Modal Footer Buttons -->
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

      <!-- no folders message -->
      <div
        v-else-if="filteredFolders.length === 0"
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
        <p class="fs-4 fw-semibold text-center">No Available Folders</p>
      </div>

      <!-- Folder List -->
      <div v-else class="folder-grid-container">
        <div
          class="folder-item text-center"
          v-for="(folder, index) in filteredFolders"
          :key="index"
        >
          <!-- Dropdown Trigger -->
          <div class="folder-dropdown">
            <button class="btn btn-sm" @click.stop="toggleFolderMenu(index)">
              <i class="bi bi-three-dots-vertical"></i>
            </button>

            <!-- Dropdown Menu -->
            <div class="folder-menu" v-if="activeFolderMenu === index">
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
                    folder.owner_email
                  )
                "
                @mouseenter="shareSubMenuIndex = null"
              >
                Permissions
              </div>

              <!-- Share Folder Option with Right-Aligned Submenu -->
              <div
                class="folder-menu-item position-relative"
                @click.stop="toggleShareSubMenu(index)"
                @mouseenter="shareSubMenuIndex = index"
              >
                Share
                <!-- Share Submenu (Right-Aligned) -->
                <div
                  class="folder-submenu"
                  v-if="shareSubMenuIndex === index"
                  @click.stop
                  @mouseenter="shareSubMenuIndex = index"
                  @mouseleave="shareSubMenuIndex = null"
                >
                  <div
                    class="folder-menu-item"
                    @click="toggleShowShareToPersonModal(folder)"
                  >
                    To a person
                  </div>
                  <div
                    class="folder-menu-item"
                    @click="toggleShareToTeam(folder)"
                  >
                    With a team
                  </div>
                </div>
              </div>

              <div
                class="folder-menu-item text-danger"
                @click="moveFolderToTrash(folder)"
                @mouseenter="shareSubMenuIndex = null"
              >
                Move to Trash
              </div>
            </div>
          </div>

          <router-link
            :to="`/folder/${folder.id}/${encodeURIComponent(
              folder.folder_name
            )}`"
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
      showNewFolderModal: false,
      showRenameFolderModal: false,
      shareSubMenuIndex: null,
      showShareToPersonModal: false,
      showShareToTeamModal: false,
      isLoading: false,
      showPermissionModal: false,
      showUpdateAccountModal: false,
      showLogoutModal: false,
      loading: false,

      //Sidebar
      sidebarVisible: window.innerWidth >= 768,
      isDesktop: window.innerWidth >= 768,

      // Data Fields
      currentUserEmail: "",
      userFname: "",
      userLname: "",
      userEmail: "",
      searchQuery: "",
      newFolderName: "",
      renameFolderName: "",
      folder_id: "",
      permission: "",
      folderName: "",
      selectedTeamId: "",
      selectedItemId: "",

      // Objects
      folders: [],
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
    searchQuery(newVal) {
      const query = newVal.trim().toLowerCase();
      if (!query) {
        this.filteredFolders = this.folders;
      } else {
        this.filteredFolders = this.folders.filter((folder) =>
          (folder.folder_name || "").toLowerCase().includes(query)
        );
      }
    },
  },

  mounted() {
    window.addEventListener("resize", this.handleResize);
    document.addEventListener("click", this.handleClickOutside);
    document.addEventListener("click", this.closeFolderMenus);
    this.isLoading = true;
    this.setCredentials();
    this.getFolders();
  },
  beforeUnmount() {
    window.removeEventListener("resize", this.handleResize);
    document.removeEventListener("click", this.handleClickOutside);
    document.removeEventListener("click", this.handleClickOutside);
    document.removeEventListener("click", this.closeFolderMenus);
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

    // Toggle Modals
    toggleAccountMenu() {
      this.showAccountMenu = !this.showAccountMenu;
      document.addEventListener("click", this.handleClickOutside);
    },
    toggleAddFolderModal() {
      this.showNewFolderModal = true;
      this.newFolderName = "";
      document.addEventListener("click", this.handleClickOutside);
    },
    toggleFolderMenu(index) {
      this.activeFolderMenu = this.activeFolderMenu === index ? null : index;
      this.shareSubMenuIndex = null;
    },
    toggleShareSubMenu(index) {
      this.shareSubMenuIndex = this.shareSubMenuIndex === index ? null : index;
    },
    openRenameModal(folder) {
      this.renameFolderName = folder.folder_name;
      this.folder_id = folder.id;
      this.showRenameFolderModal = true;
      this.activeFolderMenu = null;
      document.addEventListener("click", this.handleClickOutside);
    },
    toggleShowShareToPersonModal(folder) {
      this.folder_id = folder.id;
      this.showShareToPersonModal = true;
      this.userEmail = "";
    },
    toggleShareToTeam(folder) {
      this.folderName = folder.folder_name;
      this.folder_id = folder.id;
      this.selectedTeamId = "";
      this.showShareToTeamModal = true;
      this.getUserTeams();
    },
    async openPermissionModal(item_id, item_name, owner_email) {
      this.itemInfo.name = item_name;
      this.itemInfo.owner_email = owner_email;
      this.showPermissionModal = true;
      this.isLoading = true;

      await Promise.all([
        this.getUserPermissions(item_id),
        this.getTeamPermissions(item_id),
      ]);
      this.isLoading = false;
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
    //Close Modals
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
    closeErrorModal() {
      const errorModal = document.getElementById("errorModal");
      if (errorModal) {
        errorModal.classList.add("hidden");
      }
    },
    closeShareToTeamModal() {
      this.showShareToTeamModal = false;
      this.selectedTeamId = "";
      this.permission = "viewer";
    },
    closePermissionModal() {
      this.showPermissionModal = false;
      this.permissionData = { users: [], teams: [] };
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
    async getFolders() {
      const token = localStorage.getItem("token");
      const user_id = localStorage.getItem("user_id");
      await axios
        .post(
          `${process.env.VUE_APP_API_URL}/getUserFolders`,
          { user_id: user_id },
          {
            headers: {
              Authorization: `Bearer ${token}`,
            },
          }
        )
        .then((response) => {
          this.folders = response.data.folders || [];
          this.filteredFolders = this.folders;

          this.isLoading = false;
        })
        .catch((error) => {
          console.error("Error fetching folders:", error);
        });
      this.isLoading = false;
    },
    async createNewFolder() {
      this.closeNewFolderModal();
      this.isLoading = true;
      const token = localStorage.getItem("token");
      const user_id = localStorage.getItem("user_id");

      await axios
        .post(
          `${process.env.VUE_APP_API_URL}/createFolder`,
          {
            user_id: user_id,
            folder_name: this.newFolderName,
          },
          {
            headers: {
              Authorization: `Bearer ${token}`,
            },
          }
        )
        .then((response) => {
          if (response.data.response === "success") {
            this.getFolders();
          }
        })
        .catch((error) => {
          console.error("Error fetching folders:", error);
        });
      this.isLoading = false;
    },

    async renameFolder() {
      this.isLoading = true;
      this.closeRenameFolderModal();

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
            this.getFolders();
            this.closeRenameFolderModal();
          } else {
            alert("Failed to rename folder. Please try again.");
          }
        })
        .catch((error) => {
          console.error("Error fetching folders:", error);
        });
      this.isLoading = false;
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
    async moveFolderToTrash(folder) {
      this.isLoading = true;
      const token = localStorage.getItem("token");

      await axios
        .delete(
          `${process.env.VUE_APP_API_URL}/softDeleteFolder/${folder.id}`,
          {
            headers: {
              Authorization: `Bearer ${token}`,
            },
          }
        )
        .then((response) => {
          if (response.data.response === "success") {
            this.activeFolderMenu = null;
            this.getFolders();
          } else {
            this.showErrorModal("Something went wrong.Please try again.");
          }
        })
        .catch((error) => {
          console.error("Error fetching user teams:", error);
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
              "This folder is already shared with the selected team."
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

    // Handle Permissions
    async getUserPermissions(item_id) {
      const token = localStorage.getItem("token");

      await axios
        .post(
          `${process.env.VUE_APP_API_URL}/permissions/user`,
          {
            folder_id: item_id,
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
          if (error.response) {
            console.error("Error Response:", error.response.data);
          } else {
            console.error("Error:", error.message);
          }
        });
    },
    async getTeamPermissions(item_id) {
      const token = localStorage.getItem("token");
      await axios
        .post(
          `${process.env.VUE_APP_API_URL}/permissions/teams`,
          {
            folder_id: item_id, // or use document_id: 123
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
          if (error.response) {
            console.error("Error Response:", error.response.data);
          } else {
            console.error("Error:", error.message);
          }
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
      if (this.users.length === 0 && this.teams.length === 0) {
        this.closePermissionModal();
        return;
      }

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
            this.closePermissionModal();
            this.showErrorModal("Permissions updated successfully.");
          } else {
            this.$toast.error("One or more updates failed.");
          }
        })
        .catch((error) => {
          let message = "Failed to update permissions.";

          if (error.response && error.response.data) {
            if (typeof error.response.data === "string") {
              message = error.response.data;
            } else if (error.response.data.message) {
              message = error.response.data.message;
            } else if (error.response.data.error) {
              message = error.response.data.error;
            }
          } else if (error.message) {
            message = error.message;
          }

          this.$toast.error(message);
        })
        .finally(() => {
          this.isLoading = false;
          this.users = [];
          this.teams = [];
        });
    },
  },
};
</script>

<style scoped>
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
  min-height: 150px; /* Ensures modal remains same height */
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.modal-message-container {
  min-height: 60px; /* Locks message block height */
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

/* Responsive Styles */
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
