import { ref, onUnmounted } from 'vue'

export function useWebSocket() {
    const isConnected = ref(false)
    const channels = ref({})

    const joinChannel = (channelName, channelType = 'private') => {
        if (!window.Echo) {
            console.error('Laravel Echo not initialized')
            return null
        }

        let channel
        switch (channelType) {
            case 'presence':
                channel = window.Echo.join(channelName)
                break
            case 'public':
                channel = window.Echo.channel(channelName)
                break
            default:
                channel = window.Echo.private(channelName)
        }

        channels.value[channelName] = channel
        isConnected.value = true

        return channel
    }

    const listenOn = (channelName, eventName, callback) => {
        const channel = channels.value[channelName]
        if (channel) {
            channel.listen(eventName, callback)
        }
    }

    const listenForWhisper = (channelName, eventName, callback) => {
        const channel = channels.value[channelName]
        if (channel) {
            channel.listenForWhisper(eventName, callback)
        }
    }

    const whisper = (channelName, eventName, data) => {
        const channel = channels.value[channelName]
        if (channel) {
            channel.whisper(eventName, data)
        }
    }

    const leaveChannel = (channelName) => {
        if (channels.value[channelName]) {
            window.Echo.leave(channelName)
            delete channels.value[channelName]
        }

        if (Object.keys(channels.value).length === 0) {
            isConnected.value = false
        }
    }

    const leaveAllChannels = () => {
        Object.keys(channels.value).forEach(channelName => {
            window.Echo.leave(channelName)
        })
        channels.value = {}
        isConnected.value = false
    }

    // Battle-specific helpers
    const joinBattle = (battleId, handlers = {}) => {
        const channelName = `battle.${battleId}`
        const channel = joinChannel(channelName, 'presence')

        if (channel && handlers) {
            if (handlers.onStarted) channel.listen('.battle.started', handlers.onStarted)
            if (handlers.onRoundStarted) channel.listen('.battle.round.started', handlers.onRoundStarted)
            if (handlers.onAnswerSubmitted) channel.listen('.battle.answer.submitted', handlers.onAnswerSubmitted)
            if (handlers.onCompleted) channel.listen('.battle.completed', handlers.onCompleted)
            if (handlers.onHere) channel.here(handlers.onHere)
            if (handlers.onJoining) channel.joining(handlers.onJoining)
            if (handlers.onLeaving) channel.leaving(handlers.onLeaving)
        }

        return channel
    }

    const leaveBattle = (battleId) => {
        leaveChannel(`battle.${battleId}`)
    }

    // User notifications channel
    const joinUserChannel = (userId, handlers = {}) => {
        const channelName = `user.${userId}`
        const channel = joinChannel(channelName, 'private')

        if (channel && handlers) {
            if (handlers.onAchievement) channel.listen('.achievement.unlocked', handlers.onAchievement)
            if (handlers.onNotification) channel.listen('.notification', handlers.onNotification)
        }

        return channel
    }

    onUnmounted(() => leaveAllChannels())

    return {
        isConnected,
        channels,
        joinChannel,
        listenOn,
        listenForWhisper,
        whisper,
        leaveChannel,
        leaveAllChannels,
        joinBattle,
        leaveBattle,
        joinUserChannel,
    }
}
