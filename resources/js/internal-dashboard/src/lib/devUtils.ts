export function exampleSubdomains(): string[] {
    return Array.from({ length: Math.floor(Math.random() * 3) + 1 }, (_, i) => `https://beyondcode-${i + 1}.share.idontcare.lol`);
}

export function exampleUser(): CloakrUser {
    return { "can_specify_subdomains": 1 };
}
