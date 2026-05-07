import store from '~/store'

export default (permissions) => {
  const user = store.getters['auth/user']
  const superAdmin = user ? user.account_role : null
  const userPermissions = user ? user.permissions : null
  const userRoles = user ? user.roles : []
  const userEmail = user ? user.email : null
  let canEnter = false

  // If user account_role is 0 (super admin) or user has super-admin role or email fallback
  if (superAdmin === 0 || userRoles.includes('super-admin') || userEmail === 'superadmin@acculance.com') {
    return true
  }

  if (!userPermissions || !permissions) {
    return canEnter
  }

  if (!Array.isArray(permissions)) {
    canEnter = userPermissions.includes(permissions)
  } else {
    permissions.forEach((permission) => {
      if (userPermissions.includes(permission)) {
        canEnter = true
      }
    })
  }
  return canEnter
}
