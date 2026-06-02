import { Loader2 } from 'lucide-react';

export default function SyncOverlay({ progress }: { progress: number }) {
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-background/80 backdrop-blur-sm">
            <div className="flex w-72 flex-col items-center gap-4">
                <Loader2 className="size-8 animate-spin text-muted-foreground" />
                <p className="text-sm font-medium text-muted-foreground">
                    Syncing package...
                </p>
                <div className="w-full">
                    <div className="h-2 w-full overflow-hidden rounded-full bg-muted">
                        <div
                            className="h-full rounded-full bg-primary transition-all duration-500 ease-out"
                            style={{ width: `${progress}%` }}
                        />
                    </div>
                    <p className="mt-1.5 text-center text-xs text-muted-foreground">
                        {progress}%
                    </p>
                </div>
            </div>
        </div>
    );
}
