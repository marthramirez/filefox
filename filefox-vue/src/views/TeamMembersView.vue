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
            placeholder="Search members..."
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
        <h4 class="fw-bold text-primary mb-3">{{ $route.params.team_name }}</h4>

        <template
          v-if="currentUserRole === 'admin' || currentUserRole === 'owner'"
        >
          <button class="btn btn-outline-primary" @click="openInviteModal">
            <i class="bi bi-people-fill me-1"></i> Invite a Member
          </button>
        </template>
      </div>

      <!-- Information Modal -->
      <div id="errorModal" class="modal-overlay hidden">
        <div class="modal-box">
          <div class="modal-message-container">
            <p id="errorMessage">An error occurred.</p>
          </div>
          <button id="closeModalBtn">OK</button>
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

      <!-- Remove Member Confirmation Modal -->
      <div
        v-if="showRemoveMemberModal"
        class="modal d-block"
        tabindex="-1"
        style="background: rgba(0, 0, 0, 0.5)"
      >
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Remove Team Member</h5>
              <button
                type="button"
                class="btn-close"
                @click="closeRemoveMemberModal"
              ></button>
            </div>
            <div class="modal-body">
              Are you sure you want to remove
              <strong>{{ memberEmail }}</strong> from the team?
            </div>
            <div class="modal-footer justify-content-end">
              <button
                type="button"
                class="btn btn-link text-danger"
                @click="closeRemoveMemberModal"
              >
                Cancel
              </button>

              <button
                type="button"
                class="btn btn-link text-primary"
                @click="removeMember"
              >
                OK
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Leave Team Confirmation Modal -->
      <div
        v-if="showLeaveModal"
        class="modal d-block"
        tabindex="-1"
        style="background: rgba(0, 0, 0, 0.5)"
      >
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Leave the Team</h5>
              <button
                type="button"
                class="btn-close"
                @click="closeLeaveTeamModal"
              ></button>
            </div>
            <div class="modal-body">
              Are you sure you want to leave from the team?
            </div>
            <div class="modal-footer justify-content-end">
              <button
                type="button"
                class="btn btn-link text-danger"
                @click="closeLeaveTeamModal"
              >
                Cancel
              </button>

              <button
                type="button"
                class="btn btn-link text-primary"
                @click="leaveTeam"
              >
                OK
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Invite Member Modal -->
      <div
        v-if="showInviteMemberModal"
        class="modal fade show centered-modal"
        ref="inviteModal"
        tabindex="-1"
        role="dialog"
        aria-modal="true"
        style="display: block; background: rgba(0, 0, 0, 0.4)"
        @click.self="closeModal"
      >
        <div class="modal-dialog" role="document">
          <div class="modal-content shadow" @click.stop>
            <form @submit.prevent="inviteMember">
              <div
                class="modal-header d-flex justify-content-between align-items-center"
              >
                <h5 class="modal-title">Invite a Member</h5>
                <button
                  type="button"
                  class="btn"
                  aria-label="Close"
                  @click="closeInviteMemberModal"
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
                    v-model="memberEmail"
                    required
                    placeholder="Enter user email"
                    autofocus
                  />
                </div>

                <div class="mb-3">
                  <label for="memberRole" class="form-label">Role</label>
                  <select
                    id="memberRole"
                    class="form-select"
                    v-model="memberRole"
                    required
                  >
                    <option value="member">Member</option>
                    <option value="admin">Admin</option>
                  </select>
                </div>
              </div>

              <div class="modal-footer">
                <button
                  type="button"
                  class="btn btn-secondary"
                  @click="closeInviteMemberModal"
                >
                  Cancel
                </button>
                <button type="submit" class="btn btn-primary">Invite</button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Change User Role Modal -->
      <div
        v-if="showChangeRoleMoadal"
        class="modal fade show centered-modal"
        ref="changeRoleModal"
        tabindex="-1"
        role="dialog"
        aria-modal="true"
        style="display: block; background: rgba(0, 0, 0, 0.4)"
        @click.self="closeModal"
      >
        <div class="modal-dialog" role="document">
          <div class="modal-content shadow" @click.stop>
            <form @submit.prevent="updateRole">
              <div
                class="modal-header d-flex justify-content-between align-items-center"
              >
                <h5 class="modal-title">Change User Role</h5>
                <button
                  type="button"
                  class="btn"
                  aria-label="Close"
                  @click="closeChangeRoleModal"
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
                    v-model="memberEmail"
                    required
                    placeholder="Enter user email"
                    autofocus
                    disabled
                  />
                </div>

                <div class="mb-3">
                  <label for="memberRole" class="form-label">Role</label>
                  <select
                    id="memberRole"
                    class="form-select"
                    v-model="memberRole"
                    required
                  >
                    <option value="member">Member</option>
                    <option value="admin">Admin</option>
                    <option v-if="currentUserRole === 'owner'" value="owner">
                      Owner
                    </option>
                  </select>
                </div>
              </div>

              <div class="modal-footer">
                <button
                  type="button"
                  class="btn btn-secondary"
                  @click="closeChangeRoleModal"
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

      <!-- Error Modal -->
      <div id="errorModal" class="modal-overlay hidden">
        <div class="modal-box">
          <div class="modal-message-container">
            <p id="errorMessage">An error occurred.</p>
          </div>
          <button id="closeModalBtn">OK</button>
        </div>
      </div>

      <div class="card p-2 list-card">
        <div
          v-if="isLoading"
          class="d-flex flex-column justify-content-center align-items-center text-primary"
          style="min-height: 60vh"
        >
          <div class="spinner-border text-primary mb-3" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>

        <div v-else class="table-responsive mt-4">
          <table class="table table-hover align-middle">
            <thead class="table-light">
              <tr>
                <th>Email</th>
                <th>Role</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="member in filteredTeamMembers" :key="member.user_id">
                <td>{{ member.email }}</td>
                <td>
                  <template v-if="member.role === 'owner'">
                    <span class="badge bg-primary">Owner</span>
                  </template>
                  <template v-else>
                    {{ member.role }}
                  </template>
                </td>

                <td class="text-end">
                  <template v-if="member.user_id == currentUserId">
                    <button
                      class="btn btn-outline-danger btn-sm"
                      @click="openLeaveTeamModal(member.user_id)"
                    >
                      Leave
                    </button>
                  </template>

                  <template
                    v-else-if="
                      (currentUserRole === 'admin' ||
                        currentUserRole === 'owner') &&
                      member.role !== 'owner' &&
                      member.user_id !== currentUserId
                    "
                  >
                    <button
                      class="btn btn-outline-primary btn-sm me-2"
                      @click="
                        openChangeRoleMoadal(
                          member.user_id,
                          member.email,
                          member.role
                        )
                      "
                    >
                      Change Role
                    </button>
                    <button
                      class="btn btn-outline-danger btn-sm"
                      @click="openRemoveMemberModal(member.user_id)"
                    >
                      Remove
                    </button>
                  </template>

                  <!-- No actions for others -->
                  <template v-else>
                    <span class="text-muted small">No actions</span>
                  </template>
                </td>
              </tr>

              <tr v-if="filteredTeamMembers.length === 0">
                <td colspan="3" class="text-center text-muted">
                  No available members.
                </td>
              </tr>
            </tbody>
          </table>
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
      // for modals
      isLoading: false,
      showNewTeamModal: false,
      showInviteMemberModal: false,
      showChangeRoleMoadal: false,
      showRemoveMemberModal: false,
      showLeaveModal: false,
      showAccountMenu: false,
      showUpdateAccountModal: false,
      showLogoutModal: false,
      loading: false,

      // Layout
      sidebarVisible: window.innerWidth >= 768,
      isDesktop: window.innerWidth >= 768,

      // Data Fiels
      currentUserEmail: "",
      currentUserRole: "",
      currentUserId: "",
      team_name: "",
      userFname: "",
      userLname: "",
      userEmail: "",
      searchQuery: "",
      pageTitle: "",
      team_id: "",
      memberRole: "",
      memberEmail: "",
      member_id: "",

      // Objects and Arrays
      teamMembers: [],
      filteredTeamMembers: [],
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
        this.filteredTeamMembers = this.teamMembers;
      } else {
        this.filteredTeamMembers = this.teamMembers.filter((member) =>
          (member.email || "").toLowerCase().includes(query)
        );
      }
    },
  },
  mounted() {
    this.getPageInfo();
    this.setCredentials();
    this.loadTeamMembers();

    // Create Event handlers
    window.addEventListener("resize", this.handleResize);
    document.addEventListener("click", this.handleClickOutside);
  },
  beforeUnmount() {
    // Remove Event handlers
    window.removeEventListener("resize", this.handleResize);
    document.removeEventListener("click", this.handleClickOutside);
  },
  methods: {
    inviteMember() {
      const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

      if (!this.memberEmail || !this.memberRole) {
        this.showErrorModal("Please fill in all required fields.");
        return;
      }

      if (!emailPattern.test(this.memberEmail)) {
        this.showErrorModal("Please enter a valid email address.");
        return;
      }

      this.submitInviteMember();
    },

    async submitInviteMember() {
      this.isLoading = true;
      const token = localStorage.getItem("token");
      const user_id = localStorage.getItem("user_id");

      await axios
        .post(
          `${process.env.VUE_APP_API_URL}/inviteMember`,
          {
            team_id: this.team_id,
            email: this.memberEmail,
            owner_id: user_id,
            role: this.memberRole,
          },
          {
            headers: {
              Authorization: `Bearer ${token}`,
            },
          }
        )
        .then((response) => {
          if (response.data.response == "existing") {
            this.showErrorModal(
              "This user has already been invited. Please wait for their response."
            );
          } else if (response.data.response == "not_registered") {
            this.showErrorModal(
              "This email is not associated with a registered account."
            );
          } else if (response.data.response == "success") {
            this.showErrorModal(
              "Invitation sent successfully. Please wait for the user to respond."
            );
          } else {
            this.showErrorModal(
              "Invitation sent successfully. Please wait for the user to respond."
            );
          }
          this.closeInviteMemberModal();
        })
        .catch((error) => {
          if (error.response && error.response.status === 422) {
            this.errors = error.response.data.errors;
          } else {
            alert("Something went wrong");
          }
        });
      this.isLoading = false;
      this.memberEmail = "";
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

    closeErrorModal() {
      const errorModal = document.getElementById("errorModal");
      if (errorModal) {
        errorModal.classList.add("hidden");
      }
    },
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

    //  Navigation
    navigateTo(path) {
      this.$router.push(path);
    },

    // funtions to handle account menu
    toggleAccountMenu() {
      this.showAccountMenu = !this.showAccountMenu;
      document.addEventListener("click", this.handleClickOutside);
    },
    handleClickOutside(event) {
      const menu = this.$refs.accountMenu;
      const icon = this.$refs.accountIcon;
      if (
        menu &&
        icon &&
        !menu.contains(event.target) &&
        !icon.contains(event.target)
      ) {
        this.showAccountMenu = false;
      }
    },

    // Functions for page setup
    getPageInfo() {
      this.team_id = this.$route.params.team_id;
      this.team_name = this.$route.params.team_name;
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
    async loadTeamMembers() {
      const token = localStorage.getItem("token");
      const user_id = parseInt(localStorage.getItem("user_id"));
      this.currentUserId = user_id;
      try {
        const response = await axios.post(
          `${process.env.VUE_APP_API_URL}/getTeamMembers`,
          { team_id: this.team_id, user_id: user_id },
          {
            headers: {
              Authorization: `Bearer ${token}`,
            },
          }
        );

        if (response.data.response === "success") {
          this.teamMembers = response.data.team_members;
          this.filteredTeamMembers = this.teamMembers;

          const currentUser = this.teamMembers.find(
            (member) => member.user_id === user_id
          );

          this.currentUserRole = currentUser?.role || "member";
        } else {
          this.error = "Failed to fetch team members";
        }
      } catch (error) {
        this.error = error.message || "An error occurred";
      }
    },

    closeNewTeamModal() {
      this.showNewTeamModal = false;
    },
    performSearch() {},
    toggleSidebar() {
      this.sidebarVisible = !this.sidebarVisible;
    },
    handleResize() {
      this.isDesktop = window.innerWidth >= 768;
      this.sidebarVisible = this.isDesktop;
    },

    // Functions to handle invites
    openInviteModal() {
      this.showInviteMemberModal = true;
    },
    closeInviteMemberModal() {
      this.showInviteMemberModal = false;
    },

    //Functions to handle changing of roles
    openChangeRoleMoadal(member_id, member_email, role) {
      this.showChangeRoleMoadal = true;
      this.member_id = member_id;
      this.memberEmail = member_email;
      this.memberRole = role;
    },
    closeChangeRoleModal() {
      this.showChangeRoleMoadal = false;
      this.member_id = "";
      this.memberEmail = "";
      this.memberRole = "";
    },

    async updateRole() {
      this.isLoading = true;
      const token = localStorage.getItem("token");
      const user_id = localStorage.getItem("user_id");
      await axios
        .post(
          `${process.env.VUE_APP_API_URL}/changeRole`,
          {
            team_id: this.team_id,
            member_id: this.member_id,
            role: this.memberRole,
            requester_id: user_id,
          },
          {
            headers: {
              Authorization: `Bearer ${token}`,
            },
          }
        )
        .then((response) => {
          if (response.data.response == "success") {
            this.loadTeamMembers();
          } else {
            alert("Something went wrong");
          }
          this.closeChangeRoleModal();
        })
        .catch((error) => {
          if (error.response && error.response.status === 422) {
            this.errors = error.response.data.errors;
          } else {
            alert("Something went wrong");
          }
        });
      this.isLoading = false;
    },

    // Functions to handle member removal
    openRemoveMemberModal(member_id) {
      this.showRemoveMemberModal = true;
      this.member_id = member_id;
    },
    closeRemoveMemberModal() {
      this.showRemoveMemberModal = false;
      this.member_id = "";
    },
    async removeMember() {
      this.isLoading = true;
      const token = localStorage.getItem("token");
      const user_id = localStorage.getItem("user_id");

      await axios
        .post(
          `${process.env.VUE_APP_API_URL}/removeMember`,
          {
            team_id: this.team_id,
            member_id: this.member_id,
            requester_id: user_id,
          },
          {
            headers: {
              Authorization: `Bearer ${token}`,
            },
          }
        )
        .then((response) => {
          this.closeLeaveTeamModal();
          this.closeRemoveMemberModal();
          if (response.data.response == "owner") {
            alert("Can't remove owner");
          } else if (response.data.response == "unauthorized") {
            alert("Unauthorized");
          } else if (response.data.response == "not_member") {
            alert("Not a member");
          }
          this.loadTeamMembers();
        })
        .catch((error) => {
          if (error.response && error.response.status === 422) {
            this.errors = error.response.data.errors;
          } else {
            alert("Something went wrong");
          }
        });
      this.isLoading = false;
    },

    async leaveTeam() {
      this.isLoading = true;
      const token = localStorage.getItem("token");
      const user_id = localStorage.getItem("user_id");

      await axios
        .post(
          `${process.env.VUE_APP_API_URL}/leaveTeam`,
          {
            team_id: this.team_id,
            user_id: user_id,
          },
          {
            headers: {
              Authorization: `Bearer ${token}`,
            },
          }
        )
        .then((response) => {
          if (response.data.response === "left_team") {
            this.$router.push("/teams");
          }
        })
        .catch((error) => {
          this.showLeaveModal = false;
          if (error.response?.data?.response === "no_owner") {
            this.showErrorModal(
              "Please assign another owner before leaving the team."
            );
          } else {
            this.showErrorModal("Failed to leave the team.");
          }
        });
      this.isLoading = false;
    },

    // Functions for leaving the team
    openLeaveTeamModal(member_id) {
      this.showLeaveModal = true;
      this.member_id = member_id;
    },
    closeLeaveTeamModal() {
      this.showLeaveModal = false;
      this.member_id = "";
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

/* Sidebar */
.sidebar {
  width: 250px;
  height: 100vh;
  position: fixed;
  left: 0;
  z-index: 1000;
  overflow-y: auto;
  transition: transform 0.3s ease;
}

/* Slide transition */
.slide-enter-active,
.slide-leave-active {
  transition: transform 0.3s ease;
}
.slide-enter-from,
.slide-leave-to {
  transform: translateX(-100%);
}

/* Main content */
.main-content {
  padding-top: 80px;
  padding-left: 1rem;
  padding-right: 1rem;
}
.main-content.with-sidebar {
  margin-left: 250px;
}

/* Folders */
.list-card {
  min-height: calc(95vh - 100px);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.list-grid-container {
  overflow-y: auto;
  flex: 1;
  margin-top: 1rem;
}

/* Account Icon */
.account-icon {
  width: 40px;
  height: 40px;
  font-weight: bold;
  font-size: 18px;
  cursor: pointer;
}

/* Account Menu */
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

/* Navigation */
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

.table-responsive {
  overflow: visible !important;
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

/* Item Menus */
.dropdown-submenu .submenu {
  display: none;
  min-width: 160px;
  z-index: 1000;
}

.dropdown-submenu:hover > .submenu {
  display: block;
}
.dropdown-submenu > .dropdown-item::after {
  content: none !important;
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
