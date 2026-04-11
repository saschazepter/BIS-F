export type StatusLikedNotification = {
    like: {
        id: number;
    };
    status: {
        id: number;
    };
    trip: {
        origin: {
            id: number;
            ibnr: number;
            name: string;
        };
        destination: {
            id: number;
            ibnr: number;
            name: string;
        };
        plannedDeparture: string;
        plannedArrival: string;
        lineName: string;
    };
    liker: {
        id: number;
        username: string;
        name: string;
    };
};
