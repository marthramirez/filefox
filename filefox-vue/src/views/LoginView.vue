<template>
  <div class="auth-container" :class="{ 'sign-up-mode': isSignUp }">
    <div class="left-panel">
      <div class="brand">
        <img
          src="https://static.vecteezy.com/system/resources/previews/000/546/910/original/fox-face-logo-vector-icon.jpg"
          alt="FileFox Logo"
        />
        <h1>FileFox</h1>
      </div>
    </div>

    <div class="right-panel">
      <transition name="slide" mode="out-in">
        <div :key="isSignUp ? 'signup' : 'login'" class="form-wrapper">
          <form @submit.prevent="handleAuth">
            <h2>{{ isSignUp ? "Sign Up" : "Log In" }}</h2>
            <div v-if="isSignUp">
              <input
                type="text"
                placeholder="First Name"
                v-model="fname"
                required
              />
              <input
                type="text"
                placeholder="Last Name"
                v-model="lname"
                required
              />
            </div>
            <input type="email" placeholder="Email" v-model="email" required />

            <div class="password-field">
              <input
                :type="showPassword ? 'text' : 'password'"
                placeholder="Password"
                v-model="password"
                required
              />
              <i
                :class="`bi ${showPassword ? 'bi-eye-slash' : 'bi-eye'}`"
                @click="togglePassword"
              ></i>
            </div>

            <p v-if="authError" class="auth-error">{{ authError }}</p>

            <button type="submit" :disabled="loading">
              <span v-if="loading" class="spinner"></span>
              <span v-else>{{ isSignUp ? "Sign Up" : "Log In" }}</span>
            </button>

            <p>
              {{
                isSignUp ? "Already have an account?" : "Don't have an account?"
              }}
              <a @click.prevent="toggleAuth">{{
                isSignUp ? "Log In" : "Sign Up"
              }}</a>
            </p>
          </form>
        </div>
      </transition>
    </div>
  </div>
</template>

<script>
import axios from "axios";

export default {
  name: "AuthView",
  data() {
    return {
      isSignUp: false,
      fname: "",
      lname: "",
      email: "",
      password: "",
      loading: false,
      authError: "",
      showPassword: false,
    };
  },
  methods: {
    toggleAuth() {
      this.isSignUp = !this.isSignUp;
      this.authError = "";
    },
    togglePassword() {
      this.showPassword = !this.showPassword;
    },
    handleAuth() {
      this.authError = "";
      if (this.isSignUp) {
        this.register();
      } else {
        this.login();
      }
    },
    async register() {
      this.loading = true;
      this.authError = "";
      try {
        const response = await axios.post(
          `${process.env.VUE_APP_API_URL}/register`,
          {
            fname: this.fname,
            lname: this.lname,
            email: this.email,
            password: this.password,
          }
        );

        if (response.data.token) {
          localStorage.setItem("token", response.data.token);
          localStorage.setItem("user_id", response.data.user.id);
          this.$router.push("/dashboard");
        } else {
          this.authError = "Registration failed. Please try again.";
        }
      } catch (error) {
        console.error("error:", error);
        if (error.response) {
          if (error.response.status === 409) {
            this.authError = "Email is already registered.";
          } else if (error.response.status === 422) {
            this.authError =
              error.response.data.message || "Please check your input fields.";
          } else {
            this.authError =
              error.response.data.message || "An unexpected error occurred.";
          }
        } else {
          this.authError = "Network error. Please try again.";
        }
      } finally {
        this.loading = false;
      }
    },

    async login() {
      this.loading = true;
      try {
        const response = await axios.post(
          `${process.env.VUE_APP_API_URL}/login`,
          {
            email: this.email,
            password: this.password,
          }
        );
        if (response.data.response === "success") {
          localStorage.setItem("token", response.data.token);
          localStorage.setItem("user_id", response.data.user.id);
          this.$router.push("/dashboard");
        } else {
          this.authError = "Login failed. Please check your credentials.";
        }
      } catch (error) {
        console.error("error:", error);
        this.authError = "An error occurred during login.";
      } finally {
        this.loading = false;
      }
    },
  },
};
</script>

<style scoped>
.spinner {
  width: 20px;
  height: 20px;
  border: 3px solid rgba(255, 255, 255, 0.6);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  display: inline-block;
  vertical-align: middle;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.auth-error {
  color: red;
  font-size: 0.9rem;
  margin-bottom: 0.5rem;
  text-align: center;
}

.auth-container {
  display: flex;
  flex-direction: row;
  height: 100vh;
  width: 100vw;
  overflow: hidden;
  transition: all 0.6s ease-in-out;
}

.left-panel,
.right-panel {
  flex: 1;
  display: flex;
  justify-content: center;
  align-items: center;
  transition: all 0.6s ease-in-out;
}

.left-panel {
  background: linear-gradient(to bottom right, #0066cc, #66ccff);
  color: white;
  flex-direction: column;
  padding: 2rem;
  text-align: center;
}

.left-panel .brand img {
  width: 300px;
  height: 300px;
  border-radius: 50%;
  object-fit: cover;
  margin-bottom: 1rem;
}

.right-panel {
  background: white;
  border-top-left-radius: 40px;
  border-bottom-left-radius: 40px;
  padding: 2rem;
}

.form-wrapper {
  width: 100%;
  max-width: 400px;
  animation: fade 0.5s;
}

form {
  display: flex;
  flex-direction: column;
}

input[type="email"],
input[type="password"],
input[type="text"] {
  width: 100%;
  padding: 0.75rem;
  margin-bottom: 1rem;
  border: 1px solid #ccc;
  border-radius: 10px;
  box-sizing: border-box;
}

.password-field {
  position: relative;
}

.password-field input {
  padding-right: 40px;
}

.password-field i {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  cursor: pointer;
  color: #666;
  font-size: 20px;
}

button {
  padding: 0.75rem;
  background: #0066cc;
  color: white;
  border: none;
  border-radius: 10px;
  cursor: pointer;
  margin-top: 0.5rem;
  width: 100%;
}

button:hover {
  background: #005bb5;
}

.checkbox-label {
  font-size: 0.9rem;
  margin: 0.5rem 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.slide-enter-active,
.slide-leave-active {
  transition: all 0.5s ease;
}
.slide-enter-from {
  opacity: 0;
  transform: translateX(50%);
}
.slide-leave-to {
  opacity: 0;
  transform: translateX(-50%);
}

@media (max-width: 768px) {
  .auth-container {
    flex-direction: column;
  }

  .right-panel {
    border-radius: 40px 40px 0 0;
    width: 100%;
  }

  .left-panel {
    height: 40vh;
  }
}
</style>
