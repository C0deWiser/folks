export default [
    { path: '/', redirect: '/users' },

    {
        path: '/users',
        name: 'users',
        component: require('./screens/users/index').default,
    },

];
