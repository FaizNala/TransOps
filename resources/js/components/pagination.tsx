import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export function Pagination({ links }: { links: PaginationLink[] }) {
    if (!links || links.length <= 3) {
        return null;
    }

    return (
        <nav
            className="flex flex-wrap items-center justify-end gap-1"
            aria-label="Pagination"
        >
            {links.map((link, index) => (
                <Button
                    key={index}
                    type="button"
                    variant={link.active ? 'default' : 'ghost'}
                    size="sm"
                    disabled={!link.url || link.active}
                    onClick={() =>
                        link.url &&
                        router.get(link.url, {}, { preserveScroll: true })
                    }
                >
                    <span dangerouslySetInnerHTML={{ __html: link.label }} />
                </Button>
            ))}
        </nav>
    );
}
