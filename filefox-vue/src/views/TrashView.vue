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
          {{ "Trash" }}
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
        v-else-if="
          filteredFolders.length === 0 && filteredDocuments.length === 0
        "
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
        <p class="fs-4 fw-semibold text-center">No items in the trash.</p>
      </div>

      <!-- Item List -->
      <div v-else class="folder-grid-container">
        <!-- Display Document Items -->
        <div
          class="folder-item text-center"
          v-for="(doc, index) in filteredDocuments"
          :key="'doc-' + doc.id"
        >
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
                @click="restoreFile(doc.id)"
                @mouseenter="shareSubMenuIndex = null"
              >
                Restore
              </div>

              <div
                class="folder-menu-item"
                @click="deleteFile(doc.id)"
                @mouseenter="shareSubMenuIndex = null"
              >
                Delete Permanently
              </div>
            </div>
          </div>

          <!-- Document View -->
          <a
            @click="handleFileClick(doc)"
            class="text-decoration-none text-reset d-block pt-3"
          >
            <div class="position-relative">
              <div
                class="position-absolute top-0 start-0 px-2 py-1 small rounded-bottom fw-bold"
                :class="{
                  'text-primary': doc.file_type.toLowerCase() === 'docx',
                  'text-danger': doc.file_type.toLowerCase() === 'pdf',
                }"
              >
                {{ doc.file_type }}
              </div>
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
                @click="restoreFolder(folder.id)"
                @mouseenter="shareSubMenuIndex = null"
              >
                Restore
              </div>
              <div
                class="folder-menu-item"
                @click="deleteFolder(folder.id)"
                @mouseenter="shareSubMenuIndex = null"
              >
                Delete Permanently
              </div>
            </div>
          </div>

          <router-link
            :to="`/trashedFolder/${folder.id}/${encodeURIComponent(
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
      // for modals
      showAccountMenu: false,
      isLoading: false,
      activeFolderMenu: null,
      activeDocumentMenu: null,
      showUpdateAccountModal: false,
      showLogoutModal: false,
      loading: false,
      showPdfModal: false,

      // for layout
      sidebarVisible: window.innerWidth >= 768,
      isDesktop: window.innerWidth >= 768,

      // Data fields
      currentUserEmail: "",
      userFname: "",
      userLname: "",
      userEmail: "",
      searchQuery: "",
      selectedDocument: "",
      pdfUrl: "",

      // Objects
      folders: [],
      documents: [],
      filteredDocuments: [],
      filteredFolders: [],
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
    this.setCredentials();
    this.loadPageContents();
    window.addEventListener("resize", this.handleResize);
    document.addEventListener("click", this.handleClickOutside);
    document.addEventListener("click", this.closeFolderMenus);
    document.addEventListener("click", this.closeDocumentMenus);
  },
  beforeUnmount() {
    window.removeEventListener("resize", this.handleResize);
    document.removeEventListener("click", this.handleClickOutside);
    document.removeEventListener("click", this.closeFolderMenus);
    document.removeEventListener("click", this.closeDocumentMenus);
  },
  methods: {
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
          let filename = "document.docx";
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

    async loadPageContents() {
      this.isLoading = true;

      try {
        const [folders, documents] = await Promise.all([
          this.fetchFolders(),
          this.fetchDocuments(),
        ]);

        this.folders = folders;
        this.documents = documents;

        this.filteredFolders = [...folders];
        this.filteredDocuments = [...documents];
      } catch (error) {
        console.error("Error loading page contents:", error);
      } finally {
        this.isLoading = false;
      }
    },

    async fetchFolders() {
      const token = localStorage.getItem("token");
      const user_id = localStorage.getItem("user_id");

      try {
        const response = await axios.post(
          `${process.env.VUE_APP_API_URL}/getTrashedFolders`,
          { user_id: user_id },
          {
            headers: {
              Authorization: `Bearer ${token}`,
            },
          }
        );
        return response.data.folders || [];
      } catch (error) {
        console.error("Error fetching folders:", error);
        return [];
      }
    },

    async fetchDocuments() {
      const token = localStorage.getItem("token");
      const user_id = localStorage.getItem("user_id");

      try {
        const response = await axios.post(
          `${process.env.VUE_APP_API_URL}/getTrashedFiles`,
          { user_id: user_id },
          {
            headers: {
              Authorization: `Bearer ${token}`,
            },
          }
        );
        return response.data.documents || [];
      } catch (error) {
        console.error("Error fetching documents:", error);
        return [];
      }
    },

    toggleAccountMenu() {
      this.showAccountMenu = !this.showAccountMenu;
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
    toggleDocumentMenu(index) {
      this.activeDocumentMenu =
        this.activeDocumentMenu === index ? null : index;
      this.activeFolderMenu = null;
      this.shareSubMenuIndex = null;
      this.shareType = "document";
    },
    toggleFolderMenu(index) {
      this.activeFolderMenu = this.activeFolderMenu === index ? null : index;
      this.activeDocumentMenu = null;
      this.shareSubMenuIndex = null;
      this.shareType = "folder";
    },
    closeDocumentMenus(e) {
      if (!e.target.closest(".folder-dropdown")) {
        this.activeDocumentMenu = null;
        this.shareSubMenuIndex = null;
      }
    },
    closeFolderMenus(e) {
      if (!e.target.closest(".folder-dropdown")) {
        this.activeFolderMenu = null;
      }
    },
    async restoreFile(doc_id) {
      this.isLoading = true;
      const token = localStorage.getItem("token");
      await axios
        .post(
          `${process.env.VUE_APP_API_URL}/restoreFile`,
          { document_id: doc_id },
          {
            headers: {
              Authorization: `Bearer ${token}`,
            },
          }
        )
        .then((response) => {
          if (response.data.response === "success") {
            this.activeDocumentMenu = null;
            this.loadPageContents();
          } else {
            this.showErrorModal("Something went wrong.Please try again.");
          }
        })
        .catch((error) => {
          console.error("Error fetching user teams:", error);
        });
    },
    async deleteFile(doc_id) {
      this.isLoading = true;
      const token = localStorage.getItem("token");

      await axios
        .delete(`${process.env.VUE_APP_API_URL}/deleteFile/${doc_id}`, {
          headers: {
            Authorization: `Bearer ${token}`,
          },
        })
        .then((response) => {
          if (response.data.response === "success") {
            this.activeDocumentrMenu = null;
            this.loadPageContents();
          } else {
            this.showErrorModal("Something went wrong.Please try again.");
          }
        })
        .catch((error) => {
          console.error("Error fetching user teams:", error);
        });
    },
    async restoreFolder(folder_id) {
      this.isLoading = true;
      const token = localStorage.getItem("token");
      await axios
        .post(
          `${process.env.VUE_APP_API_URL}/restoreFolderFromTrash`,
          { folder_id: folder_id },
          {
            headers: {
              Authorization: `Bearer ${token}`,
            },
          }
        )
        .then((response) => {
          if (response.data.response === "success") {
            this.activeFolderMenu = null;
            this.loadPageContents();
          } else {
            this.showErrorModal("Something went wrong.Please try again.");
          }
        })
        .catch((error) => {
          console.error("Error fetching user teams:", error);
        });
    },
    async deleteFolder(folder_id) {
      this.isLoading = true;
      const token = localStorage.getItem("token");

      await axios
        .delete(`${process.env.VUE_APP_API_URL}/deleteFolder/${folder_id}`, {
          headers: {
            Authorization: `Bearer ${token}`,
          },
        })
        .then((response) => {
          if (response.data.response === "success") {
            this.activeFolderrMenu = null;
            this.loadPageContents();
          } else {
            this.showErrorModal("Something went wrong.Please try again.");
          }
        })
        .catch((error) => {
          console.error("Error fetching user teams:", error);
        });
    },
    performSearch() {},
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
