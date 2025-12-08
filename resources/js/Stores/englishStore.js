import { defineStore } from 'pinia'
import axios from 'axios'

export const useEnglishStore = defineStore('english', {
    state: () => ({
        profile: null,
        notifications: [],
        activeBattle: null,
        currentLesson: null,
        isLoading: false,
    }),

    getters: {
        currentLevel: (state) => state.profile?.current_level || null,
        totalXp: (state) => state.profile?.total_xp || 0,
        coins: (state) => state.profile?.coins || 0,
        gems: (state) => state.profile?.gems || 0,
        streak: (state) => state.profile?.current_streak || 0,
        eloRating: (state) => state.profile?.elo_rating || 1000,
        unreadNotifications: (state) => state.notifications.filter(n => !n.read_at).length,

        eloTier: (state) => {
            const elo = state.profile?.elo_rating || 1000
            if (elo >= 2000) return { name: 'diamond', color: '#B9F2FF', icon: '💎' }
            if (elo >= 1700) return { name: 'platinum', color: '#E5E4E2', icon: '⚪' }
            if (elo >= 1400) return { name: 'gold', color: '#FFD700', icon: '🥇' }
            if (elo >= 1100) return { name: 'silver', color: '#C0C0C0', icon: '🥈' }
            return { name: 'bronze', color: '#CD7F32', icon: '🥉' }
        },
    },

    actions: {
        async fetchProfile() {
            try {
                this.isLoading = true
                const response = await axios.get('/api/v1/english/profile')
                this.profile = response.data.data
            } catch (error) {
                console.error('Failed to fetch profile:', error)
            } finally {
                this.isLoading = false
            }
        },

        setProfile(data) {
            this.profile = data
        },

        addXp(amount) {
            if (this.profile) {
                this.profile.total_xp += amount
            }
        },

        addCoins(amount) {
            if (this.profile) {
                this.profile.coins += amount
            }
        },

        spendCoins(amount) {
            if (this.profile && this.profile.coins >= amount) {
                this.profile.coins -= amount
                return true
            }
            return false
        },

        addGems(amount) {
            if (this.profile) {
                this.profile.gems += amount
            }
        },

        updateStreak(newStreak) {
            if (this.profile) {
                this.profile.current_streak = newStreak
                if (newStreak > (this.profile.longest_streak || 0)) {
                    this.profile.longest_streak = newStreak
                }
            }
        },

        updateElo(newElo) {
            if (this.profile) {
                this.profile.elo_rating = newElo
            }
        },

        async fetchNotifications() {
            try {
                const response = await axios.get('/api/v1/english/notifications')
                this.notifications = response.data.data
            } catch (error) {
                console.error('Failed to fetch notifications:', error)
            }
        },

        async markNotificationAsRead(notificationId) {
            try {
                await axios.post(`/api/v1/english/notifications/${notificationId}/read`)
                const notification = this.notifications.find(n => n.id === notificationId)
                if (notification) {
                    notification.read_at = new Date().toISOString()
                }
            } catch (error) {
                console.error('Failed to mark notification as read:', error)
            }
        },

        async markAllNotificationsAsRead() {
            try {
                await axios.post('/api/v1/english/notifications/read-all')
                this.notifications.forEach(n => {
                    n.read_at = new Date().toISOString()
                })
            } catch (error) {
                console.error('Failed to mark all notifications as read:', error)
            }
        },

        setActiveBattle(battle) {
            this.activeBattle = battle
        },

        clearActiveBattle() {
            this.activeBattle = null
        },

        setCurrentLesson(lesson) {
            this.currentLesson = lesson
        },

        clearCurrentLesson() {
            this.currentLesson = null
        },
    },
})
