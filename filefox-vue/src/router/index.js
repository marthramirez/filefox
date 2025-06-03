import { createRouter, createWebHistory } from "vue-router";

const routes = [
  {
    path: "/",
    name: "login",
    component: () => import("../views/LoginView.vue"),
  },
  {
    path: "/dashboard",
    name: "dashboard",
    component: () => import("../views/DashboardView.vue"),
  },
  {
    path: "/folders",
    name: "Folders",
    component: () => import("../views/FolderView.vue"),
  },
  {
    path: "/folder/:folder_id/:folder_name",
    name: "FolderView",
    component: () => import("@/views/FolderContentView.vue"),
    props: true,
  },
  {
    path: "/uploads",
    name: "Uploads",
    component: () => import("../views/UploadsView.vue"),
  },
  {
    path: "/trash",
    name: "Trash",
    component: () => import("../views/TrashView.vue"),
  },
  {
    path: "/trashedFolder/:folder_id/:folder_name",
    name: "TrashedFolder",
    component: () => import("../views/TrashedFolderView.vue"),
  },
  {
    path: "/teams",
    name: "Teams",
    component: () => import("../views/TeamView.vue"),
  },
  {
    path: "/team/:team_id/:team_name",
    name: "TeamMembers",
    component: () => import("@/views/TeamMembersView.vue"),
    props: true,
  },
  {
    path: "/team/files/:team_id/:team_name",
    name: "TeamFiles",
    component: () => import("@/views/TeamFilesView.vue"),
    props: true,
  },
  {
    path: "/team/folder/contents/:folder_id/:folder_name/:team_id/:permission",
    name: "TeamFolderContents",
    component: () => import("@/views/TeamFolderContentsView.vue"),
    props: true,
  },
  {
    path: "/folder/contents/:folder_id/:folder_name",
    name: "FolderContents",
    component: () => import("@/views/TeamFolderContentsView.vue"),
    props: true,
  },
  {
    path: "/invitations",
    name: "Invitations",
    component: () => import("../views/InvitationsView.vue"),
  },
  {
    path: "/shared",
    name: "Shared",
    component: () => import("../views/SharedView.vue"),
  },
  {
    path: "/shared/folder/contents/:folder_id/:folder_name/:permission",
    name: "SharedFolderContents",
    component: () => import("@/views/SharedFolderContentsView.vue"),
    props: true,
  },
];

const router = createRouter({
  history: createWebHistory(process.env.BASE_URL),
  routes,
});

router.beforeEach((to, from, next) => {
  const token = localStorage.getItem("token");

  if (!token && to.name !== "login") {
    next({ name: "login" });
  } else if (token && to.name === "login") {
    next({ name: "dashboard" });
  } else {
    next();
  }
});

export default router;
