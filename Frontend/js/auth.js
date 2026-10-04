const AUTH_STORAGE_KEY = 'hotel_auth_session';
const USERS_STORAGE_KEY = 'hotel_users';
const SESSION_TTL = 12 * 60 * 60 * 1000; // 12 hours

const ROLES = {
  ADMIN: 'admin',
  MANAGER: 'manager',
  RECEPTION: 'reception',
  GUEST: 'guest',
};

const storage = {
  get(key) {
    try {
      return globalThis.localStorage ? globalThis.localStorage.getItem(key) : null;
    } catch (error) {
      return null;
    }
  },
  set(key, value) {
    try {
      if (globalThis.localStorage) {
        globalThis.localStorage.setItem(key, value);
      }
    } catch (error) {
      console.warn('Unable to write to storage:', error);
    }
  },
  remove(key) {
    try {
      if (globalThis.localStorage) {
        globalThis.localStorage.removeItem(key);
      }
    } catch (error) {
      console.warn('Unable to remove storage key:', error);
    }
  },
};

const sanitizeValue = (value) => String(value ?? '').trim();

const normalizeRole = (role) => {
  const allowed = Object.values(ROLES);
  const normalized = sanitizeValue(role).toLowerCase();
  return allowed.includes(normalized) ? normalized : ROLES.GUEST;
};

const validateEmail = (email) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(sanitizeValue(email));

const hashPassword = (password) => {
  const raw = sanitizeValue(password);
  let hash = 0;

  for (let i = 0; i < raw.length; i += 1) {
    hash = (hash << 5) - hash + raw.charCodeAt(i);
    hash |= 0;
  }

  return `hash_${Math.abs(hash).toString(16)}`;
};

const createUserRecord = ({ id, fullName, email, password, role }) => ({
  id,
  fullName: sanitizeValue(fullName),
  email: sanitizeValue(email).toLowerCase(),
  password: hashPassword(password),
  role: normalizeRole(role),
  createdAt: new Date().toISOString(),
  updatedAt: new Date().toISOString(),
});

const getUsers = () => {
  const raw = storage.get(USERS_STORAGE_KEY);

  if (!raw) {
    return [];
  }

  try {
    const parsed = JSON.parse(raw);
    return Array.isArray(parsed) ? parsed : [];
  } catch (error) {
    return [];
  }
};

const saveUsers = (users) => {
  storage.set(USERS_STORAGE_KEY, JSON.stringify(users));
};

const getSession = () => {
  const rawSession = storage.get(AUTH_STORAGE_KEY);

  if (!rawSession) {
    return null;
  }

  try {
    const session = JSON.parse(rawSession);
    const now = Date.now();

    if (!session || !session.expiresAt || session.expiresAt < now) {
      storage.remove(AUTH_STORAGE_KEY);
      return null;
    }

    return session;
  } catch (error) {
    storage.remove(AUTH_STORAGE_KEY);
    return null;
  }
};

const saveSession = (user) => {
  const session = {
    userId: user.id,
    email: user.email,
    role: user.role,
    fullName: user.fullName,
    issuedAt: Date.now(),
    expiresAt: Date.now() + SESSION_TTL,
  };

  storage.set(AUTH_STORAGE_KEY, JSON.stringify(session));
  return session;
};

const getCurrentUser = () => {
  const session = getSession();

  if (!session) {
    return null;
  }

  const users = getUsers();
  return users.find((user) => user.id === session.userId) || null;
};

const register = ({ fullName, email, password, role = ROLES.GUEST }) => {
  const name = sanitizeValue(fullName);
  const userEmail = sanitizeValue(email).toLowerCase();
  const userPassword = sanitizeValue(password);

  if (!name) {
    throw new Error('Full name is required.');
  }

  if (!validateEmail(userEmail)) {
    throw new Error('A valid email address is required.');
  }

  if (userPassword.length < 6) {
    throw new Error('Password must be at least 6 characters long.');
  }

  const users = getUsers();
  const emailExists = users.some((user) => user.email === userEmail);

  if (emailExists) {
    throw new Error('An account with this email already exists.');
  }

  const newUser = createUserRecord({
    id: `usr_${Date.now()}_${Math.random().toString(36).slice(2, 8)}`,
    fullName: name,
    email: userEmail,
    password: userPassword,
    role,
  });

  users.push(newUser);
  saveUsers(users);

  return {
    user: { ...newUser, password: undefined },
    session: saveSession(newUser),
  };
};

const login = ({ email, password }) => {
  const userEmail = sanitizeValue(email).toLowerCase();
  const userPassword = sanitizeValue(password);

  if (!validateEmail(userEmail)) {
    throw new Error('Please enter a valid email address.');
  }

  if (!userPassword) {
    throw new Error('Password is required.');
  }

  const users = getUsers();
  const user = users.find((item) => item.email === userEmail && item.password === hashPassword(userPassword));

  if (!user) {
    throw new Error('Invalid email or password.');
  }

  const session = saveSession(user);

  return {
    user: { ...user, password: undefined },
    session,
  };
};

const logout = () => {
  storage.remove(AUTH_STORAGE_KEY);
  return true;
};

const isAuthenticated = () => Boolean(getSession());

const hasPermission = (allowedRoles = []) => {
  const currentUser = getCurrentUser();

  if (!currentUser) {
    return false;
  }

  const roles = Array.isArray(allowedRoles) ? allowedRoles : [allowedRoles];
  return roles.map((role) => normalizeRole(role)).includes(currentUser.role);
};

const requireAuth = (allowedRoles = []) => {
  if (!isAuthenticated()) {
    throw new Error('Authentication required.');
  }

  if (allowedRoles.length && !hasPermission(allowedRoles)) {
    throw new Error('Access denied: insufficient permissions.');
  }

  return getCurrentUser();
};

const resetPassword = (email, newPassword) => {
  const userEmail = sanitizeValue(email).toLowerCase();
  const password = sanitizeValue(newPassword);

  if (!validateEmail(userEmail)) {
    throw new Error('A valid email is required.');
  }

  if (password.length < 6) {
    throw new Error('New password must be at least 6 characters long.');
  }

  const users = getUsers();
  const index = users.findIndex((user) => user.email === userEmail);

  if (index === -1) {
    throw new Error('No account found with the provided email.');
  }

  users[index].password = hashPassword(password);
  users[index].updatedAt = new Date().toISOString();
  saveUsers(users);

  return { success: true, email: userEmail };
};

const updateProfile = (updates) => {
  const currentUser = getCurrentUser();

  if (!currentUser) {
    throw new Error('User must be authenticated to update their profile.');
  }

  const users = getUsers();
  const index = users.findIndex((user) => user.id === currentUser.id);

  if (index === -1) {
    throw new Error('User profile not found.');
  }

  const nextName = sanitizeValue(updates?.fullName ?? users[index].fullName);
  const nextRole = normalizeRole(updates?.role ?? users[index].role);

  users[index].fullName = nextName || users[index].fullName;
  users[index].role = nextRole;
  users[index].updatedAt = new Date().toISOString();

  saveUsers(users);
  const updatedUser = { ...users[index], password: undefined };

  const session = getSession();
  if (session) {
    saveSession({ ...users[index] });
  }

  return updatedUser;
};

const AuthModule = {
  ROLES,
  register,
  login,
  logout,
  getCurrentUser,
  isAuthenticated,
  hasPermission,
  requireAuth,
  resetPassword,
  updateProfile,
  getUsers,
  getSession,
};

export {
  AuthModule,
  ROLES,
  register,
  login,
  logout,
  getCurrentUser,
  isAuthenticated,
  hasPermission,
  requireAuth,
  resetPassword,
  updateProfile,
  getUsers,
  getSession,
};

export default AuthModule;
